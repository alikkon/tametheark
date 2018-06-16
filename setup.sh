#!/bin/bash

# ROOT CHECK
if [[ $EUID -ne 0 ]]; then
    echo "This script must be run as root."
    exit
fi

checkyn () {
    myanswer=$1
    mydefault=$2
    if [[ -z $myanswer ]]; then
        echo "no answer found. Trying default: $mydefault"
        checkyn=$mydefault
    else
        yn=`echo $myanswer | tr [:upper:] [:lower:]`
        yn="${yn:0:1}"
        if [[ $yn == 'y' ]]; then
            checkyn='y'
        elif [[ $yn == 'n' ]]; then
            checkyn='n'
        else
            print 'Invalid selection.'
            read -p "y/n [$mydefault] " yn
            checkyn $yn $mydefault
        fi
    fi
}

# WELCOME BANNER
echo
echo "+-------------------------------+"
echo -e "|    \e[1;36mWelcome to Tame The Ark\e[0m    |"
echo "+-------------------------------+"
echo
echo "Tame The Ark is a configuration and management tool for your ARK: Survival Evolved servers."
echo "In order to configure Tame The Ark, we'll need a bit of information from you."

echo
read -p "Continue? [Y/n] " justkeepswimming
checkyn $justkeepswimming 'y'
echo $checkyn
if [[ "$checkyn" != 'y' ]]; then
    echo "Canceling."
    exit;
fi

# CHECK FOR APACHE USER
foundapacheuser=`id apache 2>/dev/null | awk '{print $1}'`
if [[ -z $foundapacheuser ]]; then
    foundapacheuser=`id www-data 2>/dev/null | awk '{print $1}'`
fi
if [[ ! -z $foundapacheuser ]]; then foundapacheuser=`echo $foundapacheuser | egrep -o '\(.*\)' | sed -e 's/(//g' -e 's/)//g'`; fi
echo
echo "What user does Apache run as?"
read -p "[$foundapacheuser] " apacheuser
if [[ -z $apacheuser ]]; then apacheuser=$foundapacheuser; fi
foundapacheuser=`id $apacheuser 2>/dev/null`
if [[ -z $foundapacheuser ]]; then
    echo "User not found. Exit and try again."
    exit
fi

# CHECK FOR APACHE GROUP
foundapachegroup=`getent group apache | cut -d':' -f1`
if [[ -z $foundapachegroup ]]; then
    foundapachegroup=`getent group www-data | cut -d':' -f1`
fi
echo
echo "What group does Apache run as?"
read -p "[$foundapachegroup] " apachegroup
if [[ -z $apachegroup ]]; then apachegroup=$foundapachegroup; fi
foundapachegroup=`getent group $apachegroup`
if [[ -z $foundapachegroup ]]; then
    echo "Group not found. Exit and try again."
    exit
fi

# CHECK FOR STEAM USER
echo
echo "What user will you run ARK as?"
read -p '[steam] ' arkuser
if [[ -z $arkuser ]]; then arkuser='steam'; fi
hassteamuser=`id $arkuser 2>&1`
if [[ $? -ne 0 ]]; then
    echo "User $arkuser not found. Attempt to create?"
    read -p '[Y/n] ' makearkuser
    checkyn $makearkuser 'y'
    if [[ $checkyn == 'y' ]]; then
        useradd -m steam
        if [[ $? -ne 0 ]]; then
            echo "Failed to create user $arkuser with 'useradd -m steam'. Please create this user and try again."
            exit
        fi
    else
        echo "User not found and user creatoin skipped. Canceling."
        exit
    fi
fi

# GET TTA LOCATION
echo
echo "Where is TameTheArk installed? "
read -p "[`pwd`] " mylocation
if [[ -z $mylocation ]]; then mylocation=`pwd`; fi
if [[ ! -d $mylocation ]]; then
    echo "No directory found at '${mylocation}'. Attempt to create it?"
    read -p '[Y/n]' makedir
    checkyn $makedir 'y'
    if [[ $checkyn == 'y' ]]; then
        mkdir "$mylocation"
        chown -R $apacheuser:$apachegroup "$mylocation"
    fi
    if [[ ! -d $mylocation ]]; then
        echo "No directory found at '${mylocation}'"
        exit
    fi
fi
if [[ "$mylocation" != `pwd` ]]; then
    echo "copy files to $mylocation?"
    read -p '[Y/n] ' installfiles
    checkyn $installfiles 'y'
    if [[ $checkyn == 'y' ]]; then
        rsync -avHCxl --exclude='conf/conf.example' --exclude='README.md' --exclude='setup.sh' --exclude='arks' --exclude='res' --exclude='.git*' ./* "$mylocation"
        chown -R $apacheuser:$apachegroup $mylocation
        chmod 0755 $mylocation
    fi
fi

# GET MYPATH LOCATION
echo
echo "Where do you want to store ark configs?"
read -p "[$mylocation/arks] " mypath
if [[ -z $mypath ]]; then mypath="$mylocation/arks"; fi
if [[ ! -d $mypath ]]; then
    echo "No directory found at '$mypath'. Create?"
    read -p '[Y/n] ' makemypath
    checkyn $makemypath 'y'
    if [[ $checkyn == 'y' ]]; then
        mkdir "$mypath"
        if [[ ! -d $mypath ]]; then echo "Failed to create path '$mypath'"; exit; fi
    fi
    chown -R $arkuser:$apachegroup $mypath
    chmod 0770 $mypath
fi

# GET STEAMCMD LOCATION
echo
if [[ -f '/usr/games/steamcmd' ]]; then
    steamfinder='/usr/games/steamcmd'
elif [[ -f '/bin/steamcmd' ]]; then
    steamfinder='/bin/steamcmd'
elif [[ -f '/home/steam/steamcmd/steamcmd.sh' ]]; then
    steamfinder='/home/steam/steamcmd/steamcmd.sh'
else
    steamfinder='not found'
fi
echo "Where is STEAMCMD installed?"
read -p "[$steamfinder] " steampath
if [[ -z $steampath ]]; then steampath="$steamfinder"; fi
if [[ ! -f $steampath ]]; then
    echo "steamcmd not found. Attempt to install?"
    read -p '[Y/n] ' installsteamcmd
    checkyn $installsteamcmd 'y'
    if [[ $checkyn == 'y' ]]; then
        if [[ -f /etc/redhat/release ]]; then
            yum -y install steamcmd
        else
            apt-get install steamcmd
        fi
        if [[ ! -f /usr/games/steamcmd ]]; then
            echo "Failed to install steamcmd using package management. Please see the following site, install steamcmd, and then run this script again."
            echo "https://developer.valvesoftware.com/wiki/SteamCMD"
            exit
        fi
    else
        echo "Canceling setup"
        exit;
    fi
fi

# GET ARK LOCATION
echo
echo "Where is ARK: Survival Evolved installed?"
read -p "[/home/steam/ArkServer] " arkpath
if [[ -z $arkpath ]]; then arkpath='/home/steam/ArkServer'; fi
if [[ ! -d "$arkpath" ]]; then
    echo "No directory found at '$arkpath'. Create?"
    read -p '[Y/n] ' makearkpath
    checkyn $makearkpath 'y'
    if [[ $checkyn == 'y' ]]; then
        mkdir "$arkpath"
        if [[ ! -d "$arkpath" ]]; then echo "Failed to create path '$arkpath'."; exit; fi
    fi
fi
if [[ ! -f $arkpath/ShooterGame/Binaries/Linux/ShooterGameServer ]]; then
    echo "ARK binaries not found in '$arkpath'. Attempt to install ARK?"
    read -p '[Y/n] ' installark
    checkyn $installark 'y'
    if [[ $checkyn == 'y' ]]; then
        chown -R $arkuser $arkpath
        sudo -u $arkuser $steampath +login anonymous +force_install_dir $arkpath +app_update 376030 +quit
    fi
fi

# APACHE SCRIPTPATH CHECK
echo
echo "What web path will Tame The Ark run under?"
read -p "[/]" scriptpath
if [[ -z $scriptpath ]]; then scriptpath='/'; fi

# USE DEFAULT MAINT INTERVALS
maintintervals=15

echo

# ATTEMPT TO CONFIGURE NOHUP, NICE, RSYNC, SUDO
nohuppath=`which nohup 2>/dev/null`
if [[ -z $nohuppath ]]; then
    echo
    echo "Could not locate nohup. Enter path to nohup:"
    read -p "" nohuppath
    if [[ ! -f $nohuppath ]]; then
        echo "nohup not found at '$nohuppath'"
        exit
    fi
else
    echo "nohup found at $nohuppath"
fi

nicepath=`which nice 2>/dev/null`
if [[ -z $nicepath ]]; then
    echo
    echo "Could not locate nice. Enter path to nice:"
    read -p "" nicepath
    if [[ ! -f $nicepath ]]; then
        echo "nice not found at '$nicepath'"
        exit
    fi
else
    echo "nice found at $nicepath"
fi

rsyncpath=`which rsync 2>/dev/null`
if [[ -z $rsyncpath ]]; then
    echo
    echo "Could not locate rsync. Enter path to rsync:"
    read -p "" rsyncpath
    if [[ ! -f $rsyncpath ]]; then
        echo "rsync not found at '$rsyncpath'"
        exit
    fi
else
    echo "rsync found at $rsyncpath"
fi

sudopath=`which sudo`
if [[ -z $sudopath ]]; then
    echo
    echo 'Could not locate sudo. Enter path to sudo:'
    read -p "" sudopath
    if [[ ! -f $sudopath ]]; then
        echo "sudo not found at $sudopath"
    fi
else
    echo "sudo found at $sudopath"
fi

echo
echo "Use sudo?"
read -p "[Y/n] " usesudo
checkyn $usesudo "y"
if [[ $checkyn == 'y' ]]; then
    usesudo=$checkyn
    if [[ $usesudo == 'y' ]]; then
        echo
        echo "Install sudoers.d file?"
        read -p "[Y/n] " installsudofile
        checkyn $installsudofile 'y'
        if [[ $checkyn == 'y' ]]; then
            echo "$apacheuser  ALL=($arkuser:$apachegroup)      NOPASSWD:$mylocation/bin/wrapper.php" > /etc/sudoers.d/tametheark
        else
            echo "Add the following line to /etc/sudoers, or create a file under /etc/sudoers.d with the contens:"
            echo "$apacheuser  ALL=($arkuser:$apachegroup)      NOPASSWD:$mylocation/bin/wrapper.php"
        fi
    fi
fi
echo

cat > $mylocation/conf/conf.php <<EOF
mypath = '$mypath'
arkpath = '$arkpath'
steampath = '$steampath'
scriptpath = '$scriptpath'
maintintervals = $maintintervals
nohup = '$nohuppath'
nice = '$nicepath'
rsync = '$rsyncpath'
EOF
if [[ $usesudo == 'y' ]]; then
    echo "sudo = '$sudopath -u $arkuser -g $apachegroup'" >> $mylocation/conf/conf.php
fi
chown $apacheuser:$apachegroup $mylocation/conf/conf.php

echo "Created conf with contents:"
cat $mylocation/conf/conf.php

echo
echo

echo "+------------------------------------------------------------+"
echo -e "|    \e[1;36mTame The Ark files configuration has been completed.\e[0m    |"
echo -e "|    \e[1;36mIf you have not already you will need to configure  \e[0m    |"
echo -e "|    \e[1;36myour apache virtualhost.                            \e[0m    |"
echo "+------------------------------------------------------------+"

