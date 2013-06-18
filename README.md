directory-s3-push-php
=======================

Simple script to copy the contents of wp-content/uploads (or any other directory) to an S3 directory when they change.

I wrote the script specifically to allow us to host all the content in our WordPress uploads directory on S3/CloudFront by simply modifiying the .htaccess file to redirect. Let me know if you need an example of how to do that.

Requires: S3.php
Set AWS Key/Secret and the name of the destination AWS S3 bucket then run:
$ php push.php

I've setup a cron job that runs once a minute and gets all newly modified or created files and pushes them up to s3, then updates the timestamp of the most recently updated file.
