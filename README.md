# Train View
PHP web app of current train list, developed on [timurarturfeher/zsr_train_view](https://github.com/timurarturfeher/zsr_train_view) code.
## How to run
Clone this repository into your web folder (typically **/var/www/html**)

    git clone https://github.com/sidet-eu/train_view
Navigate to **config.php**, and fill out the variables

    DEFINE ('DB_SERVER', 'srv');  -  MySQL server IP
    DEFINE ('DB_USER', 'usr');    -  MySQL database user
    DEFINE ('DB_PASS', 'pass');   -  MySQL database user's password
    DEFINE ('DB_NAME', 'db');     -  Database name

Now execute the **fetch_train_data.php**, and your table should be populated with train data!

We **strongly** suggest running either a by-minute crontab, or a bash script if you need more updated data. 

## Disclaimer!!!
This app is in no way approved by Železničná Spoločnosť Slovensko, or its partners, and the features in this code are purely for **educational purposes!!!**. Running this app (mainly fetch_train_data.php) can result in an IP-Restriction from their side! 
Use at your own risk, sidet.eu has no resposibility over your actions!
