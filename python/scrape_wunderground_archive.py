'''
 scrape_wunderground_archive.py

 Scrape the Weather Underground Archive for historical weather data
'''
from bs4 import BeautifulSoup
from datetime import datetime
from dateutil import rrule
from openpyxl import Workbook
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import sys


def list_to_excel(my_list, file_name):
    ''' Put the list to Excel.
        my_list needs to be a list of lists '''
    wb = Workbook(write_only=True)
    ws = wb.create_sheet()

    for row in my_list:
        ws.append(row)
    wb.save(file_name)


# Initialize
weather_station_id = sys.argv[1]  # 'KCACAMPO13'
start_date = sys.argv[2]  # datetime.strptime('2017-01-01', '%Y-%m-%d')
end_date = sys.argv[3]  # datetime.strptime('2018-07-31', '%Y-%m-%d')
file_name = 'wunderground_{}_{}to{}.xlsx'.format(weather_station_id, start_date.strftime('%Y%m%d'), end_date.strftime('%Y%m%d'))
csv = []
header = []
header_has_units = False
previous_month = None
previous_dt = None

# Iterate each day and grab the weather data from the history table
for dt in rrule.rrule(rrule.DAILY, dtstart=start_date, until=end_date):
    month = dt.strftime('%m')
    day = dt.strftime('%Y-%m-%d')
    today = dt.strftime('%m/%d/%Y')

    # Flush to disk if we're in a new month
    if previous_month and month != previous_month:
        first_day_of_month = previous_dt.strftime('%Y-%m-01')
        last_day_of_month = previous_dt.strftime('%Y-%m-%d')
        file_name = 'wunderground_{}_{}to{}.xlsx'.format(weather_station_id, previous_dt.strftime('%Y%m01'), previous_dt.strftime('%Y%m%d'))
        print("FLUSHING CACHE TO CSV FOR {} TO {}".format(first_day_of_month, last_day_of_month))
        csv.insert(0, header)
        list_to_excel(csv, file_name)
        csv = []
        header = []
        header_has_units = False

    # Start harvesting the day's data
    previous_month = month
    previous_dt = dt
    url = 'https://www.wunderground.com/history/daily/us/ca/campo/KCACAMPO13/date/{}'.format(day)
    history_table_html = None
    # Keep trying until you get it!
    number_of_attempts = 1
    while not history_table_html:
        driver = webdriver.Chrome()
        driver.get(url)
        try:
            if number_of_attempts == 1:
                print("GETTING DAY {} FOR WEATHER STATION {}".format(day, weather_station_id))
            elif number_of_attempts > 1:
                print("GETTING DAY {} FOR WEATHER STATION {} (ATTEMPT #{})".format(day, weather_station_id, number_of_attempts))
            table = WebDriverWait(driver, 30).until(
                EC.presence_of_element_located((By.ID, "history-observation-table"))
            )
            history_table_html = table.get_attribute('outerHTML')
        except:
            history_table_html = None
        finally:
            driver.quit()
            number_of_attempts += 1

    # Begin parsing the HTML table
    history_table = BeautifulSoup(history_table_html, 'html.parser')
    if not header:
        header = ['Date']
        for thead in history_table.select('thead'):
            for tr in thead.select('tr'):
                for th in tr.select('th'):
                    header.append(th.text.replace('\n', '').strip())
                break

    for tbody in history_table.select('tbody'):
        for tr in tbody.select('tr'):
            data_row = [today]
            skip_row = False
            col_index = 1
            for td in tr.select('td'):
                # Split the value from the units, and convert the time to 24h
                td_text = [x.replace('\n', '').strip() for x in td.text.split(' ')]
                value = td_text[0]
                if (len(td_text) > 1 and td_text[1].upper() in ['AM', 'PM']):
                    value += ' ' + td_text[1]
                    value = datetime.strptime(value, '%I:%M %p').strftime('%H:%M')
                if 'time' in value.lower():
                    skip_row = True
                    break  # skip the row if it is an inline repeat of the header
                data_row.append(value)
                if not header_has_units:
                    for span in td.select('span'):
                        units = span.text
                        if units:
                            header[col_index] += ' (' + units + ')'
                        break
                col_index += 1
            header_has_units = True
            if not skip_row:
                csv.append(data_row)
    del table

# One final flush to disk
first_day_of_month = previous_dt.strftime('%Y-%m-01')
last_day_of_date_range = previous_dt.strftime('%Y-%m-%d')
file_name = 'wunderground_{}_{}to{}.xlsx'.format(weather_station_id, previous_dt.strftime('%Y%m01'), end_date.strftime('%Y%m%d'))
print("FLUSHING CACHE TO CSV FOR {} TO {}".format(first_day_of_month, last_day_of_date_range))
csv.insert(0, header)
list_to_excel(csv, file_name)

print(" -- DONE --")
