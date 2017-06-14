

# The end of the measureit project ?! #

On Oct 5, 2010 I share the first running version from measureit with the community. The hardware I use was the energy monitors from Current Cost Ltd. This company went into liquidation in September 2015 and meanwhile you can not buy their hardware any more. As a result the community is not longer growing. 

As a result I did not invest much time to bring forward this project. Now there is a point I had to ask me if I should stop this project.

I take a quick look on the market but did not found other smart meters that support reading the data directly. The new trend is to push the data into the cloud and take money from the customer to see the own data...
If you know alternative hardware [feel free to post your ideas](https://github.com/lalelunet/measureit/issues/27)

The new trend is home automation, I am very interested in and of course I will spend time to develop cool stuff. If you think you had a idea what measureit can do you can [post your idea here](https://github.com/lalelunet/measureit/issues/28)

I would like to use this opportunity to say thank you to all contributor, supporter, creator, promoter, translater, user, ..... It was a great time with all of you! 

**Thank you very much to all of you!**

Please feel free to contact me nevertheless. I am listening always to your feedback  ;)


Regards

Thomas

# Welcome to the measureit Project #

Measureit allows you to have your own server to store your voltage and temperature data. It has a  easy to use web interface that is completely written in javascript and jquery to see your power consumption.

Currently it works with the hardware from Current Cost (currentcost.com) with the Classic, Envi and the EnviR

You are able to see the detailed consumption in real time or see your consumption from the last week or from a user defined time range and has a overview of the energy consumption and cost per sensor / sensor position / year / month /day usage. There are also different graphs to display a weekly or yearly overview in a comparison or you can see the data usage per weekday or per hour or.....

Measureit has clamp support. You can see the data from every clamp and the total usage from a sensor with multiple clamps.

## Raspberry Pi ##

Measureit runs on a Raspberry Pi without problems.
I recommend the usage from a pre configured iso image where all software is installed.

The setup is lightweight, very fast and did not need much system resources so you can do additional things with your pi.

[Click here for more informations](https://github.com/lalelunet/measureit/wiki/Raspberry-Pi).

The storage database is optimized to store millions of voltage data without using much storage. In 4 months a sensor writes about 1.000.000 data sets that use just about 25 MB of storage. Measureit delete old data automatically after a user defined period.

The hardware used with this project comes from currentcost that offers a base station with up to 9 sensors. Measureit supports the usage up to all 9 sensors and multiple clamps per Sensor.

You can send the consumption data and the produced data from your solar system to the [http://pvoutput.org PVOutput] Service too. Every clamp, sensor or IAM can send data to their service. So you are able to see your data from everywhere without running a server at your home. They are creating nice graphs too :)

There are options to create notifications per email or twitter if a user defined usage / generation is over / under a user defined value / time. 

More informations about measureit you can find in the [wiki](https://github.com/lalelunet/measureit/wiki/) from the project.


## Multiple languages ##

Measureit is able to speak your language. If your language is not in the list and you wish to help so feel free to translate this small text file. It is just about 10 minutes of work. Send your translated file and it will be included as soon as possible.

The file to translate you will find here:
https://github.com/lalelunet/measureit/blob/master/measureit_public_html/lng/en_EN.txt

Currently available languages are:
  * English
  * German
  * Italian
  * French
  * Dutch
  * Catalan
  * Spanish
  * Brazilian Portuguese

Send you translation to measureitsoft@gmail.com

There are some language settings that are missing because of the extension from measureit. If you want to help you can translate them here:

https://docs.google.com/document/d/1fKyB458Xb4k1DM5_Vemj-lqx7-h01zAfeONYFyEfmJs/edit?usp=sharing


**FEEDBACK WANTED**

## SUPPORT ##

If you like measureit feel free to give me a tip  :)

https://www.paypal.com/cgi-bin/webscr/?cmd=_s-xclick&hosted_button_id=TEHJHXLPSU3C6


I will use the money for drinking beer or other reasonable things :)

There is also a group to discuss about your experience with measureit or anything else that is relatet to energy monitoring :)

http://groups.google.com/group/measureit/

[![Analytics](https://ga-beacon.appspot.com/UA-6114760-13/lalelunet/measureit?pixel)](https://github.com/lalelunet/measureit)

