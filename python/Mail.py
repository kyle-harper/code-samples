'''
  Mail.py

  Class to handle email notifications.
'''
from email.mime.text import MIMEText
from email.mime.application import MIMEApplication
from email.mime.multipart import MIMEMultipart
from smtplib import SMTP


class Mail():
    def __init__(self, server, port=False, username=False, password=False, encryption=False, email_from='kyle.p.harper@gmail.com'):
        self.server = server
        self.port = port
        self.username = username
        self.password = password
        self.email_from = email_from

    def send(self, email_to, subject, body, attachment_file=False,
             attachment_name=False):
        """Sends an email.

        TODO: refactor.

        Parameters:

        @email_to: a [list] of email address strings.
        @subject: a string that will appear in the Subject line.
        @body: what will appear in the body of the email.
        @attachment_file (optional): a string as a path to a file, or a [list] of
                                     paths as strings.
        @attachment_name (optional): a string of what the file will be named in
                                     the email, or a [list] of names as strings
                                     that are respective to the attchmentfile list.
        """
        msg = MIMEMultipart()
        msg["Subject"] = subject
        msg.attach(MIMEText(body, 'html'))
        if attachment_file and attachment_name:
            if isinstance(attachment_file, list) and isinstance(attachment_name, list):
                for attachment, attach_name in zip(attachment_file, attachment_name):
                    part = MIMEApplication(open(attachment, "rb").read())
                    part.add_header(
                        "Content-Disposition", "attachment",
                        filename=attach_name
                    )
                    msg.attach(part)
            else:
                part = MIMEApplication(open(attachment_file, "rb").read())
                part.add_header(
                    "Content-Disposition", "attachment",
                    filename=attachment_name
                )
                msg.attach(part)

        # send the email:
        if self.port:
            server = SMTP(self.server, self.port)
        else:
            server = SMTP(self.server)
        if self.username:
            server.login(self.username, self.password)
        server.sendmail(self.email_from, email_to, msg.as_string())
        server.quit()
