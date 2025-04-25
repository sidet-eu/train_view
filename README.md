## Note!
### As of 25.4.2024, there has been multiple invalid requests, typically in the morning times on ŽSR side.
### It may happen to you, if you are going to run the script autonomously, that you'll end up with an empty table, due to how the script works.


# Train View

PHP web app of current train list, developed on [timurarturfeher/zsr_train_view](https://github.com/timurarturfeher/zsr_train_view) code.

## How to run

Clone this repository into your web folder (typically **/var/www/html**)

  

    git clone https://github.com/sidet-eu/train_view

Navigate to **config.php**, and fill out the variables

  

    DEFINE ('DB_SERVER', 'srv'); - MySQL server IP
    DEFINE ('DB_USER', 'usr'); - MySQL database user
    DEFINE ('DB_PASS', 'pass'); - MySQL database user's password
    DEFINE ('DB_NAME', 'db'); - Database name

Then run **create_table.sql** in your database, or run this;

    CREATE  TABLE  train_data (
	    StanicaZCislo varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
	    StanicaDoCislo varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
	    Nazov varchar(150) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    TypVlaku varchar(10) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    CisloVlaku varchar(10) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    NazovVlaku varchar(100) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    Popis text  COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    Meska int  DEFAULT  NULL,
	    Dopravca varchar(255) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    InfoZoStanice varchar(255) COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    MeskaText text  COLLATE utf8mb4_general_ci DEFAULT  NULL,
	    date_added timestamp  NOT NULL  DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

  

Now execute the **fetch_train_data.php**, and your table should be populated with train data!

  

We **strongly** suggest running either a by-minute crontab, or a bash script if you need more updated data.

    #!/bin/bash
    
    #useful for 15 second update
    while  true; do
    	php  fetch_train_data.php
    	sleep  15
    done

## Disclaimer!!!

This app is in no way approved by Železničná Spoločnosť Slovensko, or its partners, and the features in this code are purely for **educational purposes!!!**. Running this app (mainly fetch_train_data.php) can result in an IP-Restriction from their side!

Use at your own risk, sidet.eu has no resposibility over your actions!