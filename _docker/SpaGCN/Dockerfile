# Use the official Python image as the base image
FROM python:3.9

# Install R and required packages
RUN apt-get update && apt-get install -y r-base

# Set the working directory in the container
WORKDIR /spatialGE

# Install the required Python libraries
RUN pip install numpy==1.20.0
RUN pip install pandas==1.5.3

RUN pip install SpaGCN==1.2.7


RUN pip install anndata scanpy
RUN pip install torch
#RUN pip install scipy

RUN pip install pyreadr==0.5.0

#RUN pip install anndata2ri
#RUN pip install rpy2
# Set any environment variables if needed
# ENV MY_VARIABLE=value

# Copy your application code into the container (if you have any)
# COPY . /app

# Specify the command to run when the container starts
CMD [ "python" ]
