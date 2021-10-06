PROGRESS_FILE=/tmp/dependancy_atlas_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Launch install of Atlas dependancies : "
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
sudo apt-get clean
echo 30 > ${PROGRESS_FILE}
sudo apt-get update
echo 40 > ${PROGRESS_FILE}
sudo apt-get install -y rsync
echo 50 > ${PROGRESS_FILE}
sudo apt-get install -y cloud-guest-utils
echo 60 > ${PROGRESS_FILE}
sudo apt-get install -y ethtool
echo 70 > ${PROGRESS_FILE}
sudo apt-get install -y hostapd dnsmasq
rm ${PROGRESS_FILE}
echo "Everything is successfully installed!"
