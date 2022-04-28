#ShareG

##1. create databse
```
    create database shareg default character set utf8mb4 collate utf8mb4_unicode_ci;
```

##2. config env
```
    cp .env.example .env
    php artisan key:generate

```

##3. install database migration
```
    php artisan migrate --seed
```

##4. start up horizon process dev
```
    php artisan horizon &
    php artisan horizon:terminate

```

## supervisor configuration
## /etc/supervisord.d/horizon.ini
```
    [program:horizon]
    process_name=%(program_name)s
    command=php /data/apps/gandb_laravel_backend/artisan horizon
    autostart=true
    autorestart=true
    startsecs=5
    startretries=3
    stopwaitsecs=3600
    user=admin
    redirect_stderr=true
    stdout_logfile=/data/apps/gandb_laravel_backend/storage/logs/horizon.log
    stdout_logfile_maxbytes=64MB
    stdout_logfile_backups=14
```
```
    supervisorctl stop horizon
    supervisorctl restart horizon
```

##5. create public storage link
```
    mkdir -p storage/app/public
    php artisan storage:link
```

##default account
###admin
| email                 | password |
|-----------------------|:--------:|
| admin@shareg.com      | pa@123456|

###client
| email                 | password |
|-----------------------|:--------:|
| client@shareg.com     | pa@123456|

##plugins
1. https://github.com/brozot/Laravel-FCM

##develop command
```
    composer dumpautoload
    
    php artisan make:command TestCommand

    php artisan make:model User

    php artisan make:migration create_table_users

    php artisan migrate:fresh --seed

    php artisan migrate:refresh --seed
    
```

##nocode marks
nocode-component
nocode-component-container
nocode-layout
nocode-edit-type='link'
nocode-edit-type='background'
nocode-edit-type='text'
nocode-edit-type='image'
nocode-edit-type='copy'
nocode-edit-type='delete'

