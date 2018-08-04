'''
  ArcLog.py

  Class to handle runtime logging.
'''
import arcpy
import time


class ArcLog():
    def __init__(self, log_file=False):
        self.log_file = log_file

    def append(self, write_string, include_timestamp=True, stdout_only=False):
        """Writes the input string to the CLI, log file (if specified), and the
        script window (if executed from a toolbox).

        Args:
            write_string: An input string that will be written to both the log file,
                           the interpreter/cli, and the arcgis immediate window.
            include_timestamp (bool): Include timestamp at the top of the log entry?
            stdout_only (bool): Omit logging to file and instead just spit to stdout?
        Return:
            None
        """
        msg = "\n%s" % (str(write_string))
        if include_timestamp:
            msg = ("\n%s" % (time.strftime("(%Y-%m-%d %H:%M:%S)"))) + msg
        if self.log_file and not stdout_only:
            with open(self.log_file, "a") as textfile:
                textfile.write(msg)
        arcpy.AddMessage(msg)
