<a href="https://garson.co/en/"><h1 align="center">
  <img src="./logo.jpg"/><br>  
</h1></a>

<div align="center">
	<a href="https://play.google.com/store/apps/details?id=com.smallorder&amp;hl=ru"><img src="./assets/google-play.png" height=50px alt=""></a>
	<a href="https://itunes.apple.com/us/app/dgarson/id1247782976?mt=8"><img src="./assets/appstore.svg" height=50px alt=""></a>
</div>

___

* [About](#remote-food-order)
* [Start to use](#start-to-use-dgarson)
* [Deploying](#deploying)
* [Problems](#problems)
* [Support](#support-dgarson)
* [Change Log](#change-log)
* [Contributing](#change-log)
* [Created by](#change-log)

# **Remote food order**

**Client**

Project dGarson helps meet a hungry person and a cook who wants to feed him without the participation of third parties. The client simply walking on the street or being in the office using a mobile application can find a cafe nearby, order food, choose time when he wants to pick up an order, and online to find out the status of his order without spending time in the queue. Thanks to the Push Notification technology.   When order status is redy client go to the cafe and receives his order.

**Cafe**

For a cafe you need to register and get access to the admin area. After this you will receive orders online and you simply transfer them to the kitchen. Where the chef changes the status of the order. Please watch video below.

**For you my dear reader**

For the development of business ideas. You can add to the order menu the delivery. Where the customer enters his address and time of delivery. Also you will need to connect SMS service and sign contract with the сourier service or taxi service  in your city. When the order comes from the client, the server sends sms to the courier service and they deliver the order. Good luck!

# **Start to use dGarson**

[![](https://img.youtube.com/vi/zBIbe6KM9ZA/0.jpg)](https://www.youtube.com/watch?v=zBIbe6KM9ZA)

You can independently test the application in action. 
Download the application from [PlayMarket](https://play.google.com/store/apps/details?id=com.smallorder&amp;hl=ru) or [AppStore](https://itunes.apple.com/us/app/dgarson/id1247782976?mt=8). 

Then install it and go through simple registration using `any phone number` and confirmation code (`9999`). Note this path will test the application in `test mode`. 

Real institutions will `not accept your orders`.

 After successful registration you get to the list of available companies. For convenience institutions you should visit the resource https://app.garson.co/ (login: `admin@admin.com`; password: `secret`) and log in to the test account with administrator rights. After these steps, you can use the application according to the [video](https://www.youtube.com/watch?v=zBIbe6KM9ZA).

**`Please pay attention to the possibility of using the application in the test mode simultaneously by several users. This can lead to unexpected orders or changes in the menu of institutions.`**

*`Also, in order to avoid the possibility of downloading obscene content, each uploaded photo (to a menu component or an institution) will be replaced by a similar one from our database.`*

**For greater responsiveness of the application every day at 0:00 AM TCB database is completely cleared**

**Visit:** The [Acropolium website](https://acropolium.com/) and follow on:<br />
* [Facebook](https://www.facebook.com/acrornd)
* [LinkedIn](https://www.linkedin.com/company/acropolium)
* [Xing](https://www.xing.com/companies/acropolium)
* [Twitter](https://twitter.com/acropolium)


##Requirements
Before deploying this project you must to install next:
  * Apache - version 2.0 or higher
  * MySQL
  * PHP - version 5.5.9 or greater
  * Composer
  * Twilio account
  * Firebase account

##Deploying
 ### Install Apache 
  Open your terminal and run command ```sudo apt-get install apache2```.
  Open folder ```/etc/apache2/sites-available```, create file ```[YOUR_PROJECT_NAME].conf``` and open it in any text editor.
  Put such code there: 
  ```apache
    <VirtualHost *:80>
            ServerName [YOUR SERVER HOST NAME]
            DirectoryIndex index.php index.html index.htm
            DocumentRoot  [PATH_TO_PROJECT_FOLDER]/public
            <Directory  [PATH_TO_PROJECT_FOLDER]/public>
                    Options FollowSymLinks MultiViews
                    AllowOverride All
                    Require all granted
            </Directory>
            ErrorLog /var/log/apache2/error.log 
            LogLevel notice
            CustomLog /var/log/apache2/access.log combined
    </VirtualHost>   
   ```
  Then run in terminal:
  ```bash
    $ sudo a2ensite [YOUR_PROJECT_NAME].conf
    $ sudo service apache 2 reload
  ```
 ### Install MySQL
  Install the `mysql-server` package and choose a secure password when prompted:
  ```bash
  $ sudo apt-get install mysql-server
  ```
  After installation, log in MySQL with command:
  ```bash
  $ mysql -u root -p
  ```
  Enter MySQL’s root password, and you’ll be presented with a MySQL prompt.
  Create a database and a user with permissions for it.
  ```mysql
  CREATE DATABASE YOUR_DATABASE_NAME;
  CREATE USER YOUR_USERNAME IDENTIFIED BY 'YOUR_PASSWORD';
  GRANT ALL PRIVILEGES ON YOUR_DATABASE_NAME.* TO YOUR_USERNAME;
  FLUSH PRIVILEGES;
  ```
  After this manipulation exit from MySQL:
  ```mysql
   quit
  ```
 ### Install PHP
   In this example, we use PHP version 7.0, but you can install any version higher than 5.5.9.
   Install PHP and needed extensions by command:
   ```bash
    $ sudo apt-get install php7.0 libapache2-mod-php7.0 php7.0-mysql php7.0-curl, php7.0-json php7.0-mbstring php7.0-mcrypt php7.0-xml
   ```
   Then restart Apache server:
   ```bash
    $ sudo service apache2 restart
   ```
   
 ### Configure project
  Clone repository with project, after go to the project directory and run ```composer install```.
  Copy ```.env.example``` to ```.env``` file, then open terminal in project directory and run command ```php artisan key:generate```.
  If everything is set up correctly, you should see something like:
  > Application key [HERE WILL BE APPLICATION key] set successfully.
  
  Open ```.env``` file in any text editor and edit it. 
  Set next env variables:
  * `APP_URL` - url where back-end will be hosted.
  * `DB_HOST` - set database host.
  * `DB_PORT` - set database port (by default in MySQL 3306).
  * `DB_DATABASE` - set database name.
  * `DB_USERNAME` - set database username.
  * `DB_PASSWORD` - set database password.
  * `TWILIO_ACCOUNT_SID` - set Twilio account SID (you can get it, in your twilio account).
  * `TWILIO_AUTH_TOKEN` - set Twilio auth token (you can get it, in your twilio account).
  * `TWILIO_NUMBER` - set Twilio number (you can get it, in your twilio account).
  * `FCM_SERVER_KEY` - set server key for fcm.
  * `FCM_SENDER_ID` - set sender id for fcm.
  
  **Notice**: you can get `FCM_SERVER_KEY` and `FCM_SENDER_ID` after you configure your mobile project in firebase console.
  You can find it in settings your mobile project on firebase.
  
  Then you need to migrate database. In terminal run command ```php artisan migrate```.
  If command success, you will see something like:
  >Migration table created successfully.\
   Migrated: 2014_10_12_000000_create_users_table\
   Migrated: 2014_10_12_100000_create_password_resets_table\
   Migrated: 2016_12_06_141149_create_data_model\
   Migrated: 2016_12_21_142838_user_push_tokens\
   Migrated: 2016_12_26_143627_order_desired_time\
   Migrated: 2016_12_30_075406_user_multiple_tokens\
   Migrated: 2017_01_17_094846_menu_translate\
   Migrated: 2017_01_24_155900_locations\
   Migrated: 2017_02_08_080857_menu_item_logo\
   Migrated: 2017_02_08_150455_device_token_locale\
   Migrated: 2018_02_14_132441_company_translate
   
   Then you need to seed your database. Run command ```php artisan db:seed```. It will create three users with basic roles.
   By default email and password: 
   
   | Email      | Password | Role          |
   |------------|----------|---------------|
   | admin@a.c  | 123456   | Administrator |
   | owner@a.c  | 123456   | Owner         |
   | worker@a.c | 123456   | Worker        |

   And now command ```php artisan storage:link``` 
   
   P.S. If you want to change default seed data, you should edit `[YOUR_PROJECT_DIRECTORY]/database/seeds/DatabaseSeeder.php`.
   **Do it on your own risk.**
   


# **Contributing**

Found a bug? Report it on GitHub Issues and include a code sample. Please state which version of React/ReactNative you are using! This is vitally important.

Written something cool in d'Garson? Please tell us about it in the  email support@dgarson.com

# **Created by**

<div align='center'>d'Garson is a <a href="https://acropolium.com/">Acropolium</a> production.</div><BR/>

<div align='center'> 
<p>
 <a href="https://acropolium.com/">
<img src="./assets/company-logo.svg" alt="Acropolium" ></div>
</a>
</p>
<div align='center'>Created by <a href="https://acropolium.com/">Acropolium command</a>.</div>


<div align='center'>All rights reserved.</div>



