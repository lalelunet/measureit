#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
******************************************************************************
* SimpleMail sends emails with and without attachments
*
* Filename: simplemail.py
* Created: 2004-10-06 Gerold - http://gerold.bcom.at/
* License: LGPL: http://www.opensource.org/licenses/lgpl-license.php
* Requirements: Python >= 2.3: http://www.python.org/
* History: http://gelb.bcom.at/svn/pub/simplemail/trunk/history.rst
* Trac: http://gelb.bcom.at/trac/simplemail/
*
* Simple Example:
*
*   from simplemail import Email
*   Email(
*       from_address = "sender@domain.at",
*       to_address = "recipient@domain.at",
*       subject = "This is the subject",
*       message = "This is the short message body."
*   ).send()
*
* Aenderung 2009-05-15: Modul wurde auf utf-8 umgestellt
*
******************************************************************************
"""

import os.path
import sys
import time
import smtplib
import mimetypes
import email
from email import Encoders
from email.Header import Header
from email.MIMEText import MIMEText
from email.MIMEMultipart import MIMEMultipart
from email.Utils import formataddr
from email.Utils import formatdate
from email.Message import Message
from email.MIMEAudio import MIMEAudio
from email.MIMEBase import MIMEBase
from email.MIMEImage import MIMEImage


# Exceptions
#----------------------------------------------------------------------
class SimpleMailException(Exception):
    """SimpleMail Base-Exception"""
    def __str__(self):
        return self.__doc__

class NoFromAddressException(SimpleMailException):
    """No sender address"""
    pass

class NoToAddressException(SimpleMailException):
    """No recipient address"""
    pass

class NoSubjectException(SimpleMailException):
    """No subject"""
    pass

class AttachmentNotFoundException(SimpleMailException):
    """Attachment not found"""
    def __init__(self, filename = None):
        if filename:
            self.__doc__ = 'Attachment not found: "%s"' % filename

# for backward compatibility
SimpleMail_Exception = SimpleMailException 
NoFromAddress_Exception = NoFromAddressException
NoToAddress_Exception = NoToAddressException
NoSubject_Exception = NoSubjectException
AttachmentNotFound_Exception = AttachmentNotFoundException
#----------------------------------------------------------------------


# UTF-8 codierte E-Mails sollen nicht Base64-Codiert, sondern
# Quoted-Printable codiert werden
email.charset.add_charset("utf-8", email.charset.QP, email.charset.QP, "utf-8")


class Attachments(object):
    """Email attachments"""

    def __init__(self):
        self._attachments = []


    def add_filename(self, filename = ''):
        """Adds a new attachment"""
        
        self._attachments.append(filename)


    def count(self):
        """Returns the number of attachments"""
        
        return len(self._attachments)


    def get_list(self):
        """Returns the attachments, as list"""
        
        return self._attachments


class Recipients(object):
    """Email recipients"""

    def __init__(self):
        self._recipients = []


    def add(self, address, caption = u""):
        """
        Adds a new address to the list of recipients
        
        :param address: email address of the recipient
        :param caption: caption (name) of the recipient
        """
        
        # ToDo: Die Umwandlung sollte später mal, nicht mehr hier, sondern erst
        # beim Verwenden des Empfängers umgewandelt werden. Dann weiß man
        # das gewünschte Encoding. Das Encoding muss ich hier leider fest
        # im Quellcode verankern. :-(
        if isinstance(caption, unicode):
            caption = str(Header(caption, charset = "utf-8"))
        self._recipients.append(formataddr((caption, address)))


    def count(self):
        """Returns the quantity of recipients"""
        
        return len(self._recipients)


    def __repr__(self):
        """Returns the list of recipients, as string"""
        
        return str(self._recipients)


    def get_list(self):
        """Returns the list of recipients, as list"""
        
        return self._recipients


class CCRecipients(Recipients):
    """Carbon copy recipients"""
    pass


class BCCRecipients(Recipients):
    """Blind carbon copy recipients"""
    pass


class Email(object):
    """One email, which can sent with the 'send'-method"""

    def __init__(
        self,
        from_address = "",
        from_caption = "",
        to_address = "",
        to_caption = "",
        subject = "",
        message = "",
        smtp_server = "localhost",
        smtp_user = "",
        smtp_password = "",
        attachment_file = "",
        user_agent = "",
        reply_to_address = "",
        reply_to_caption = "",
        use_tls = False,
        header = {},
    ):
        """
        Initializes the email object
        
        :param from_address: the email address of the sender
        :param from_caption: the caption (name) of the sender
        :param to_address: the email address of the recipient
        :param to_caption: the caption (name) of the recipient
        :param subject: the subject of the email message
        :param message: the body text of the email message
        :param smtp_server: the ip-address or the name of the SMTP-server
        :param smtp_user: (optional) Login name for the SMTP-Server
        :param smtp_passwort: (optional) Password for the SMTP-Server
        :param user_agent: (optional) program identification
        :param reply_to_address: (optional) Reply-to email address
        :param reply_to_caption: (optional) Reply-to caption (name)
        :param use_tls: (optional) True, if the connection should use TLS to encrypt.
        :param header: (optional) Additional header fields as dictionary.
            You can use this parameter to add additional header fields.
            Allready (internal) used header fields are: "From", "Reply-To", "To", 
            "Cc", "Date", "User-Agent" and "Subject". (case sensitive)
            The items of this dictionary replace internal used header fields.
        """
        
        self.from_address = from_address
        if isinstance(from_caption, unicode):
            from_caption = str(Header(from_caption, charset = "utf-8"))
        self.from_caption = from_caption
        self.recipients = Recipients()
        self.cc_recipients = CCRecipients()
        self.bcc_recipients = BCCRecipients()
        if to_address:
            self.recipients.add(to_address, to_caption)
        self.subject = subject
        self.message = message
        self.smtp_server = smtp_server
        self.smtp_user = smtp_user
        self.smtp_password = smtp_password
        self.attachments = Attachments()
        if attachment_file:
            self.attachments.add_filename(attachment_file)
        self.content_subtype = "plain"
        self.content_charset = "utf-8"
        self.header_charset = "us-ascii"
        self.statusdict = None
        if user_agent:
            self.user_agent = user_agent
        else:
            self.user_agent = (
                "SimpleMail Python/%s (http://www.python-forum.de/post-18144.html)" 
            ) % sys.version.split()[0]
        self.reply_to_address = reply_to_address
        if isinstance(reply_to_caption, unicode):
            reply_to_caption = str(Header(reply_to_caption, charset = "utf-8"))
        self.reply_to_caption = reply_to_caption
        self.use_tls = use_tls
        self.header_fields = header


    def send(self):
        """
        de: Sendet die Email an den Empfaenger.
            Wird das Email nur an einen Empfaenger gesendet, dann wird bei
            Erfolg <True> zurueck gegeben. Wird das Email an mehrere Empfaenger
            gesendet und wurde an mindestens einen der Empfaenger erfolgreich
            ausgeliefert, dann wird ebenfalls <True> zurueck gegeben.
            
            Wird das Email nur an einen Empfaenger gesendet, dann wird bei
            Misserfolg <False> zurueck gegeben. Wird das Email an mehrere 
            Empfaenger gesendet und wurde an keinen der Empfaenger erfolgreich
            ausgeliefert, dann wird <False> zurueck gegeben.
        """
        
        #
        # pruefen ob alle notwendigen Informationen angegeben wurden
        #
        if len(self.from_address.strip()) == 0:
            raise NoFromAddressException()
        if self.recipients.count() == 0:
            if (
                (self.cc_recipients.count() == 0) and 
                (self.bcc_recipients.count() == 0)
            ):
                raise NoToAddressException()
        if len(self.subject.strip()) == 0:
            raise NoSubjectException()
        
        #
        # Wenn die Nachricht oder Subject UNICODE sind, 
        # dann nach self.content_charset umwandeln
        #
        if isinstance(self.subject, unicode):
            if self.header_charset.lower() != "us-ascii":
                self.subject = self.subject.encode(self.header_charset)
        if isinstance(self.message, unicode):
            self.message = self.message.encode(self.content_charset)
        
        #
        # Email zusammensetzen
        #
        if self.attachments.count() == 0:
            # Nur Text
            msg = MIMEText(
                _text = self.message,
                _subtype = self.content_subtype,
                _charset = self.content_charset
            )
        else:
            # Multipart
            msg = MIMEMultipart()
            if self.message:
                att = MIMEText(
                    _text = self.message,
                    _subtype = self.content_subtype,
                    _charset = self.content_charset
                )
                msg.attach(att)
        
        # Empfänger, CC, BCC, Absender, User-Agent, Antwort-an 
        # und Betreff hinzufügen
        from_str = formataddr((self.from_caption, self.from_address))
        msg["From"] = from_str
        if self.reply_to_address:
            reply_to_str = formataddr((self.reply_to_caption, self.reply_to_address))
            msg["Reply-To"] = reply_to_str
        if self.recipients.count() > 0:
            msg["To"] = ", ".join(self.recipients.get_list())
        if self.cc_recipients.count() > 0:
            msg["Cc"] = ", ".join(self.cc_recipients.get_list())
        msg["Date"] = formatdate(time.time())
        msg["User-Agent"] = self.user_agent
        try:
            msg["Subject"] = Header(
                self.subject, self.header_charset
            )
        except(UnicodeDecodeError):
            msg["Subject"] = Header(
                self.subject, self.content_charset
            )
        # User defined header_fields
        if self.header_fields:
            for key, value in self.header_fields.items():
                msg[key] = value
        
        msg.preamble = "You will not see this in a MIME-aware mail reader.\n"
        msg.epilogue = ""
        
        # Falls MULTIPART --> zusammensetzen
        if self.attachments.count() > 0:
            for filename in self.attachments.get_list():
                # Pruefen ob Datei existiert
                if not os.path.isfile(filename):
                    raise AttachmentNotFoundException(filename = filename)
                # Datentyp herausfinden
                ctype, encoding = mimetypes.guess_type(filename)
                if ctype is None or encoding is not None:
                    ctype = 'application/octet-stream'
                maintype, subtype = ctype.split('/', 1)
                if maintype == 'text':
                    fp = file(filename)
                    # Note: we should handle calculating the charset
                    att = MIMEText(fp.read(), _subtype=subtype)
                    fp.close()
                elif maintype == 'image':
                    fp = file(filename, 'rb')
                    att = MIMEImage(fp.read(), _subtype=subtype)
                    fp.close()
                elif maintype == 'audio':
                    fp = file(filename, 'rb')
                    att = MIMEAudio(fp.read(), _subtype=subtype)
                    fp.close()
                else:
                    fp = file(filename, 'rb')
                    att = MIMEBase(maintype, subtype)
                    att.set_payload(fp.read())
                    fp.close()
                    # Encode the payload using Base64
                    Encoders.encode_base64(att)
                # Set the filename parameter
                att.add_header(
                    'Content-Disposition', 
                    'attachment', 
                    filename = os.path.split(filename)[1].strip()
                )
                msg.attach(att)
        
        #
        # Am SMTP-Server anmelden
        #
        smtp = smtplib.SMTP()
        if self.smtp_server:
            smtp.connect(self.smtp_server)
        else:
            smtp.connect()
        
        # TLS-Verschlüsselung
        if self.use_tls:
            smtp.ehlo()
            smtp.starttls()
            smtp.ehlo()
        
        # authentifizieren
        if self.smtp_user:
            smtp.login(user = self.smtp_user, password = self.smtp_password)
        
        #
        # Email versenden
        #
        self.statusdict = smtp.sendmail(
            from_str, 
            (
                self.recipients.get_list() + 
                self.cc_recipients.get_list() + 
                self.bcc_recipients.get_list()
            ), 
            msg.as_string()
        )
        smtp.close()
        
        # Rueckmeldung
        return True


def test():

    # Einfaches Beispiel
    from simplemail import Email
    
    print "Einfaches Beispiel"
    print "------------------"
    Email(
        from_address = "server@gps.gp",
        smtp_server = "gps.gp",
        to_address = "gerold@gps.gp",
        subject = "Einfaches Beispiel (öäüß)",
        message = "Das ist der Nachrichtentext mit Umlauten (öäüß)",
    ).send()
    print "Fertig"
    print
    
    # Einfaches Beispiel nur mit CC-Adresse
    from simplemail import Email
    
    print "Einfaches Beispiel mit CC-Adresse"
    print "---------------------------------"
    email = Email(
        from_address = "server@gps.gp",
        smtp_server = "gps.gp",
        subject = "Einfaches Beispiel mit CC-Adresse (öäüß)",
        message = "Das ist der Nachrichtentext mit Umlauten (öäüß)"
    )
    email.cc_recipients.add("gerold@gps.gp", "Gerold")
    email.send()
    print "Fertig"
    print

    # Einfaches Beispiel nur mit BCC-Adresse
    from simplemail import Email
    
    print "Einfaches Beispiel mit BCC-Adresse"
    print "---------------------------------"
    email = Email(
        from_address = "server@gps.gp",
        smtp_server = "gps.gp",
        subject = "Einfaches Beispiel mit BCC-Adresse (öäüß)",
        message = "Das ist der Nachrichtentext mit Umlauten (öäüß)"
    )
    email.bcc_recipients.add("gerold@gps.gp", "Gerold Penz")
    email.send()
    print "Fertig"
    print

    # Komplexeres Beispiel mit Umlauten und Anhaengen
    from simplemail import Email
    
    print "Komplexeres Beispiel mit Umlauten und Anhaengen"
    print "-----------------------------------------------"
    email = Email(
        smtp_server = "gps.gp"
    )
    
    # Absender
    email.from_address = "server@gps.gp"
    email.from_caption = "Gerolds Server"

    # Antwort an
    email.reply_to_address = "gerold@gps.gp"
    email.reply_to_caption = "Gerold Penz (Antwortadresse)"

    # Empfaenger
    email.recipients.add("gerold@gps.gp", "Gerold Penz (lokal)")
    # Zum Testen wird hier eine unbekannte Adresse eingeschoben.
    email.recipients.add("unbekannte-adresse@gps.gp", "UNBEKANNT")
    
    # Betreff
    email.subject = "Komplexeres Beispiel"
    
    # Nachricht
    email.message = (
        "Das ist ein etwas komplexeres Beispiel\n"
        "\n"
        "Hier steht normaler Text mit Umlauten (öäüß).\n"
        "Groß kann man sie auch schreiben -- ÖÄÜ.\n"
        "\n"
        "mfg\n"
        "Gerold\n"
        ":-)"
    )

    # Anhaenge (die Pfade sind an meine Testsysteme angepasst)
    if sys.platform.startswith("win"):
        filename1 = r"H:\GEROLD\Bilder und Videos\Blumencorso Seefeld 2006\000013.JPG"
        filename2 = r"H:\GEROLD\Bilder und Videos\Blumencorso Seefeld 2006\000018.JPG"
    else:
        filename1 = "/home/gerold/GEROLD/Bilder und Videos/Blumencorso Seefeld 2006/000013.JPG"
        filename2 = "/home/gerold/GEROLD/Bilder und Videos/Blumencorso Seefeld 2006/000018.JPG"
    if os.path.isfile(filename1):
        email.attachments.add_filename(filename1)
        email.attachments.add_filename(filename2)
    
    # Senden und Statusmeldungen anzeigen
    if email.send():
        if email.recipients.count() == 1:
            print "Die Nachricht wurde erfolgreich an den Empfaenger versendet."
        else:
            if email.statusdict:
                print \
                    "Die Nachricht wurde mindestens an einen der Empfaenger " + \
                    "versendet.\nEs sind aber auch Fehler aufgetreten:"
                for item in email.statusdict:
                    print "  Adresse:", item, "Fehler:", email.statusdict[item]
            else:
                print \
                    "Die Nachricht wurde an alle Empfaenger " + \
                    "erfolgreich versendet."
    else:
        print "Die Nachricht wurde nicht versendet."
    
    print "Fertig"
    print 

    # HTML-Email
    from simplemail import Email
    
    print "HTML-Email"
    print "----------"
    email = Email(
        from_address = "server@gps.gp",
        smtp_server = "gps.gp",
        to_address = "gerold@gps.gp",
        header = {"Reply-To": "gerold@gps.gp"},
    )
    email.subject = "Das ist ein HTML-Email"
    email.content_subtype = "html"
    email.message = \
        "<h1>Das ist die Überschrift</h1>\n" + \
        "<p>\n" + \
        "  Das ist ein <b>kleiner</b><br />\n" + \
        "  Absatz.\n" + \
        "</p>\n" + \
        "<p>\n" + \
        "  Das ist noch ein <i>Absatz</i>.\n" + \
        "</p>\n" + \
        "<p>\n" + \
        "  mfg<br />\n" + \
        "  Gerold<br />\n" + \
        "  :-)\n" + \
        "</p>"
    if email.send():
        print "Die Nachricht wurde gesendet."
    else:
        print "Die Nachricht wurde nicht versendet."

    print "Fertig"
    print
    
    ## Googlemail-Email-Beispielcode
    #from simplemail import Email
    #
    #Email(
    #    from_address = "EMAILNAME@gmail.com", 
    #    to_address = "EMPFAENGER@domain.xx",
    #    subject = "Googlemail Test",
    #    message = "Das ist ein Googlemail Test.",
    #    smtp_server = "smtp.googlemail.com:587",
    #    smtp_user = "EMAILNAME", # Emailadresse ohne "@gmail.com"
    #    smtp_password = "PASSWORT", 
    #    use_tls = True # Muss auf True gesetzt sein
    #).send()
    

if __name__ == "__main__":
    # Wenn dieses Modul mit dem Parameter "test" aufgerufen wird, 
    # dann werden Test-Emails gesendet.
    if "test" in sys.argv[1:]:
        test()

