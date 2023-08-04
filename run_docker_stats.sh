#!/bin/bash

# Load variables from .env file to get DB credentials
source .env

# Enable nocasematch option for case-insensitive comparison
shopt -s nocasematch

count=0
while [ $count -lt 5 ]; do

    #get docker stats about containers
    docker_stats_output=$(docker stats --no-stream --format '{"container":"{{ .Name }}","memory":"{{ .MemUsage }}","cpu":"{{ .CPUPerc }}"}')
    #Each line contains stats for a container
    echo "$docker_stats_output" | while IFS= read -r line; do

        #if there are containers running
        if [ -n "$line" ]; then

            #parse the data to get container's memory and cpu usage
            container=$(echo "$line" | jq -r '.container')
            memory=$(echo "$line" | jq -r '.memory')
            cpu=$(echo "$line" | jq -r '.cpu')
            timestamp=$(date +"%Y-%m-%d %H:%M:%S")

            #get the numeric value of cpu % utilisation
            cpu="${cpu//[^0-9.]}"

            #calculate memory usage in megabytes
            memory=$(echo "$memory" | awk '{print $1}')
            memory_amount="${memory//[^0-9.]}"
            #memory_unit="${memory%%[A-Za-z]*}"
            if [[ $memory == *"GiB"* ]]; then
                memory_amount=$(echo "$memory_amount * 1000" | bc)
            fi

            #echo "$timestamp - $container - $cpu - $memory_amount"

            mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -D"$DB_DATABASE" -e "INSERT INTO task_stats (task, memory, cpu, timestamp) VALUES ('$container', '$memory_amount', '$cpu', '$timestamp');"
        #else
        #    echo "The variable is empty."
        fi

    done

    count=$((count + 1))
    sleep 10

done

# Disable nocasematch option to revert to case-sensitive comparison
shopt -u nocasematch
