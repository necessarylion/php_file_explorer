# PHP File Explorer

![Demo Image](assets/demo.png)

How to install

step 1. 

`git clone https://github.com/necessarylion/php_file_explorer.git`

step 2. 

`composer install`

step 3. 

- if you use local storage 
  - create storage folder 

  - `mkdir storage`
  - `sudo chmod -R 777 storage`
  - set STORAGE_TYPE='local'

```
STORAGE_TYPE='local'  # aws, local
FOLDER_NAME="my-guest-folder"
```

- if you use AWS S3
  - copy .env.example to .env and write credentials in .env file
  - `cp .env.example .env`
  - set STORAGE_TYPE='aws'

```
STORAGE_TYPE='aws'  # aws, local
AWS_KEY=
AWS_SECRET=
AWS_ENDPOINT=
AWS_REGION=
BUCKET_NAME=
FOLDER_NAME="my-guest-folder"
```

### Password : admin

### NOTE
This PHP File Explorer is modification of webcdn/File-Explorer (standalone) `https://github.com/webcdn/File-Explorer`