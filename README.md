directory-s3-mirror-php
=======================

Simple script to copy the contents of wp-content/uploads to an S3 directory when they change.

Requires: S3.php
Set AWS Key/Secret and the name of the destination AWS S3 bucket then run:
$ php push.php

I've setup a cron job that runs once a minute and gets all newly modified or created files and pushes them up to s3, then updates the timestamp of the most recently updated file.