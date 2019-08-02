# WUSM Hashing Identification

Creates a means of using hashing to replace or substitute using name and birth date identifier information to reference a record by changing the standard REDCap "Add new record" button which is completely replaced with a set of fields to enter First name, Last name, Birth date and an "Add Participant" button.
********************************************************************************

## Getting Started

The module takes a participants first name, last name, birth date and generates a hash code value from that information.  The hash code is used to insure unique entries and prevent duplication of participants that will be saved and stored.  Also the hash code allows the removal of the first name, last name and birth date from the data as required for International purposes and still allow unique record keeping in a seamless manner.

While adding a participant, and that participant has been previously entered, the system will be able to determine the previous entry and will produce an error message stating **"Participant (RECORD ID  DAG NAME) already exists.  Please contact the Project Administrator."**  If the participant is new to the system, then their data will be saved and the form will display allowing any additional data entry desired.

Users that are assigned to a DAG (Data Access Group) will have their DAG information automatically set and the record will save to their group.


********************************************************************************

## Requirements

**Project fields**

There are some special and critical fields to have in the project.

(these are configurable as to the specific variable names used) [Configuration example of Variable Settings](?prefix=wusm_hashing_identification&page=docs/settingsVariables.png)


(see the project external module WUSM Hashing Identification settings)

The location and hash code are essential.  

>For example: 
> 1. hash_code
> 2. location_flag
>
> Note: you may name your variables anything as you wish, for discussion, the above are being used.

The field **location_flag** is a **Location** and is a **radio button** with values **0 = us, 1 = international**.

You MUST use those values.

The fields **first name**, **last name**, **birth date** and **birth year** are also needed. 

Birth year, will be an integer, min 0, max 9999, it could make sense to have it 1900 to 2019 or max at the current year.

Birth date, should be, ultimately, Y-M-D.  System configuration may play a role in how the date format flows.  Validation has been tested as M-D-Y format for input.  And system configuration may affect dates.

The variable names can be different than shown here, however you must also make sure that the same names are set in the configuration settings as well.  

Other fields listed here such as middle name and suffix are not required fields, but as shown below, do keep in mind that you may wish to hide them for international participants.


Note: branching logic (Show the field ONLY if:)

Note: hashcode Field Annotation is @HIDDEN

********************************************************************************

---
| Variable      |    Label         | Data Type     |  Data Validation           | Branching Logic       |
| :---          |    :---          |        ---:   |    :----:                  |         ---:          |
| location_flag |  Location flag   | radio         | 0 = us, 1 = international  |                       |
| hashcode      |  Hashcode        | text          |                            |                       |
| lastname      |  Last name       | text          |                            | [location_flag] = '0' |
| firstname     |  First name      | text          |                            | [location_flag] = '0' |
| middlename    |  Middle          | text          |                            | [location_flag] = '0' |
| suffix        |  suffix          | text          |                            | [location_flag] = '0' |
| birthdate     |  Birth Date      | text          | date_mdy                   | [location_flag] = '0' |
| birth_year    |  Birth Year      | text          | integer, Min: 0, Max: 9999 |                       |
---
 	 	
********************************************************************************
 	 	 
### Configuration
Set the field names the same as the Project field variable names as seen in the code book.  (These are currently free form text fields and do not use the field-id type).

Also be sure to put in the name of the Instrument for this data.

#### Settings

Project Using Hashing:    The project ID that is using this module.

Prefix to Record ID: (optional).  You could have a three letter prefix, for example, to a record ID.

International DAGs:   The name of the DAG, preferably the variable name used, however, the system can interpret. (See Usage, below)

The international DAGs list helps identify which entries need the extra data handling.  International entries do not save the identifier information, such as name and birth date data.  Note that birth year is saved.

Instrument Name: The name of the instrument being used for this data.

Log Mode: check if desire to debug any issues and track down possible errors.


********************************************************************************

### Usage

#### Adding DAGS

You can add DAGs to the project and then for any DAGs that are considered non-US (International), please also then add the International ones to the External Module config settings using the DAG names.

Simply enter the text of the name, click the plus button to add additional entries (use the same exact names as set in the DAGs).  These entries are required to allow the module to determine and operate correctly for handling International data properly.  The first, last names and birth date for International data will not be saved in the project data.  The only reference to the participant is the generated hash code.  

Use the given DAG name that was set when creating the DAG group, for instance, use "CHUM (University of Montreal)" in the above config file.

Please note: REDCap DAG names utilize a "Unique group name" format (that is internally generated and not stored), which is the given DAG name, converted to all lowercase with spaces changed to underscores and the whole result limited to 18 characters (trailing underscores are removed).  Only spaces (converted to underscores), letters and numbers are used, other characters are discarded in the conversion.  **Please keep DAG names unique within the first 18 characters** as repeating names get appended random letters to guarantee uniqueness, however this will result in mis-match issues for this process as there is no way to separate which is the intended name to the correct corresponding ID number for the above naming system.


**Log Mode**

The log mode when checked is used for system and data diagnosis when there are needs to analyze how the system is functioning, normally this option remains unchecked.



********************************************************************************
### Authors

* **David Heskett** - *Initial work*

### License

This project is licensed under the MIT License - see the [LICENSE](?prefix=wusm_hashing_identification&page=LICENSE.md) file for details

### Acknowledgments

* Inspired by WUSM REDCap Team.

