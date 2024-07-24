# Use the official Python image as the base image
FROM python:3.9

#Install Moffitt's CA certificates
RUN apt-get update -qq && \
    apt-get upgrade -y && \
    apt-get clean all && \
    apt-get install -y --no-install-recommends \
        ca-certificates locales wget libcurl4-openssl-dev libxml2-dev libssl-dev && \
    apt-get clean && \
    apt-get purge && \
    rm -rf /var/lib/apt/lists/* && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-ca.crt \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-ca.cer" && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-ca-int.crt \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-ca-int.cer" && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-chain \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-chain" && \
    chmod 644 /usr/local/share/ca-certificates/*moffitt* && \
    update-ca-certificates --verbose && \
        echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && locale-gen en_US.utf8 && update-locale LANG=en_US.UTF-8

# Install R and required packages
RUN apt-get update && apt-get install -y r-base

# Set the working directory in the container
WORKDIR /spatialGE

# Install the required Python libraries
RUN pip install numpy==1.22.4
RUN pip install MILWRM==1.1.1
RUN pip install scanpy==1.9.3
RUN pip install harmonypy==0.0.10
RUN pip install pandas==2.2.2
RUN pip install pyreadr==0.5.0


RUN pip install anndata
# RUN pip install torch
# RUN pip install scipy


# Specify the command to run when the container starts
CMD [ "python" ]
