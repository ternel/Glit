# Welcome to Glit [![build status](https://secure.travis-ci.org/wpottier/Glit.png)](https://secure.travis-ci.org/wpottier/Glit)

Glit is a free project hosting & collaborative platform application wich used git versionning system.
This platform is powered by symfony2 and work with gitolite git server.
All sources were released under the MIT license

## Main features

* Per user or team (organization) projects
* git project management
* file browser
* Wiki/Issues/Documents/forks... on projects

## Requirements

* Debian (or any debian related) system
* PHP > 5.3
* Webserver like apache, nginx or others
* MySQL

## Install

* First, copy the `app/config/parameters.ini.dist` into `app/config/parameters.ini` and edit the values according to your databse configuration

* With your terminal, go the glit directory and run the following command (to install symfony and vendors) :

    php bin/vendor install

* Run the automated installer

    php app/console glit:install
     
## Contribute

I actually work alone on this project but I'm open to all help proposition.
Feel free to submit all bug or ideas you have on the github issues, send me pull request,...

     
## Contacts
 
You can contact me through twitter in french or english : [@wizad](https://twitter.com/wizad)