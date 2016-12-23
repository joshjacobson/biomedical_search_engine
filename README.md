# Biomedical Search Engine

This search engine employs several big data concepts to make the Unified Medical Language System ([UMLS](https://www.nlm.nih.gov/research/umls/)) Knowledge Source accessible to any user. The search engine features three main components: query handling, classification, and visualization. The user can search a medical term with our system to retrieve a classification of the term determined by Mahout Naive Bayes, relevant information (definition, symptoms, etc.), and a visualization of neighboring/related medical concepts using a combination of IBM System G’s graph storage and a Plotly graph. The programming languages used to make this possible are PHP, HTML, Java, and Python. This repository contains all the require files to deploy this search engine in your own environment.

## Dependencies
  1. Relational Database Manager System (RDBMS), we recommend [MySQL Server](http://dev.mysql.com/downloads/).
  2. Web Server, we recommend [Apache](https://httpd.apache.org/download.cgi).
  3. [Java JDK 8](http://www.oracle.com/technetwork/java/javase/downloads/jdk8-downloads-2133151.html).
  4. [PHP](http://php.net/downloads.php).
  5. [IBM System G](http://systemg.research.ibm.com/download.html).
  5. [Python](https://www.python.org/downloads/).
  6. Python Packages: [python-igraph](http://igraph.org/python/), [json](https://docs.python.org/2/library/json.html), and [plotly](https://plot.ly/python/).

## Steps to deploy the system.
  1. Sign up for license at the [UMLS Terminology Services](https://uts.nlm.nih.gov//license.html).
  2. Create the database named umls in your RDBMS that will host the UMLS Schemas. For instance CREATE DATABASE IF NOT EXISTS umls CHARACTER SET utf8 COLLATE utf8_unicode_ci for MySQL. 
  3. Read the [UMLS Tutorial](https://www.nlm.nih.gov/research/umls/new_users/online_learning/OVR_001.html) and [UMLS Reference Manual](https://www.ncbi.nlm.nih.gov/books/NBK9676/) to get familiar with the system requirements and be able to access and load, to the umls database created in step 2, the Metathesaurus and Semantic Network Knowledge Sources.
  4. Run the file named Normalize_UMLS.sql in the MySQL directory. This will create a database named sandbox that normalized and subset the umls database improving performance.
  5. Read the IBM System G [gShell overview](http://systemg.research.ibm.com/1.5.0/doc/gshell.html).
  6. Replace the line [file_location] in the file contained in the SYSTEMG directory with the location of the concept.txt and relationship.txt created with MySQL queries. Pass the modified file to gShell (gShell interactive < filename) to load the concepts, semantics and their relationships into System G.
  7. In the PHP directory edit the following files to configure your database credentials: mysqlconnect_umls.php and mysqlconnect_sandbox.php
  8. Create an account in [plot.ly](https://plot.ly) and modify the file contained on the PYTHON directory to enter the username and key of your account on the following line py.sign_in('user', 'key').
  9. Copy the content of the of the PHP, JAVA and PYTHON to the sudirectory of the root directory of your web server where you want the system to be access.
  10. Go to this subdirectory in your browsers and add at the end of it "/lookup.php" and you should be able to start using the our system.
  
Note: Please make sure that apache have read and write privileges to the location were you installed system G. If you encounter any other problems and can't figure it a solution, please feel free to contact me at jaa2220@cumc.columbia.edu I'll be happy to assists you.
  
  
