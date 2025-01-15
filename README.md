# Mass Send-It
A REDCap external module that combines REDCap's "Send-IT" feature with "Alerts & Notifactions". Schedule bulks to be send to participants setup through record lists or logical conditions. Attach record specific documents by binding through a reference to your file repository. Control document sending and receiving options, such as password protection, expiration time or custom email messages (with piping supported).

## Setup
To install the module through your REDCap Control Center, search for "Mass Send-It" in the REDCap External Modules Repository. After successful installation enable the module for the project that you would like to use bulk sending.

## Usage
Navigate to the External Modules page "Mass Send-It". There you can find an overview of available bulks under the tab "My Bulks" which will be active per default. Another tab show the Notificaiton Log of analogous to "Alerts & Notifications."

### My Bulks
**Create a new bulk**

To create a bulk click on "Add New Bulk" which will open the bulk creation dialog. The bulk creation process is divided into 4 steps:
*Before you start:* Ensure that you have read the steps and prepare your records, documents and parameters correctly, before you actually create a new bulk.

1. Selecting Bulk recipients
Define how the records will be selected and insert the record selection parameters. If you chose to select records by a plain record list, the record selection parameter is a comma-separated list of records. In the other case, by defining a filter logic, you will provide a filter logic that will be evaluated on bulk creation and generate a record list automatically.

2. File Repository binding
You must specify parameters to attach a file to each record. The following parameters are required to make the binding work:

- File Repository folder: Chose the folder where you have already uploaded your documents.
- File Extension: The file extension of the documents you have uploaded.
- File Repository Reference: This is a record specific id that will be used to reference to the document. 

The File Repository binding will concat the parameters to a record specific path, like [file_repository_folder]/[file_repository_reference].[file_repository_extension].

3. Message Settings
Setup how the bulk message(s) will be send. Besides basic settings, you can configure the password to be set randomly or through a record specific field. Also you can chose to send the password in a separate message with additional custom settings.


4. Set the Schedule / Expiration
Define the exact date and time when to send the Bulk and optionally, the date and time when the bulk should expire, i.e. document download link not available for download anymore.


### Notification Log

The notification log has been build identical to "Alerts & Notification" log. Navigate there by clicking on "Notification Log" on the Mass Send-It module page of your project. Per default, the future notification view will be loaded. To switch to past notifications, click on "View past notifications".

You can change the "Begin time" or "End time" by setting another date and time in the datetime picker. 
Additionally, you can filter for a bulks or records.

## Developer Notes

The module stores bulk data into the external modules logs (although this is not best choice in terms of performance, it is the recommended approach by the EMF team). The module registers a cron job that checks every 60 seconds if there are any valid bulks to be send. Bulks will be processed in reasonable chunks (similar to Alert's notification generation). The download and authorization flow of documents has been adopted from the "Send-It" feature, whereby the original REDCap document share link will be included into messages sent.

The module has been scanned for possible vulnerabilities with Psalm. To run psalm use:

`<redcap-root> .\bin\scan.bat modules\mass_send_it_v1.0.0\`

To run module tests use REDcap's phpunit binary to mitigate version conflicts in abstract testing classes:

`..\..\<redcap_vXX.YY.ZZ>\UnitTests\vendor\bin\phpunit tests`

*Please note:* Tests currently only run after manually adding test documents with correct references to "Test Project 1".

For TypeScript output run:

`npx tsc --watch` during development or `npx tsc --build` for production

