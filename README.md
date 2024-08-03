# NoraGo Plugin for OpenCart 3.0.3.7

## Introduction

Welcome to the NoraGo Plugin for OpenCart 3.0.3.7! This plugin allows you to integrate the NoraGo system with your OpenCart store, offering enhanced functionalities and a superior user experience.

## Requirements

* OpenCart 3.0.3.7
* PHP 7.0 or higher

## Installation

### Step 1: Download the Plugin
Download the NoraGo plugin package from the official repository or the GitHub releases page.

### Step 2: Upload the Files
Extract the contents of the downloaded file and upload the files to the main directory of your OpenCart installation, maintaining the directory structure.

### Step 3: Install via Admin Panel
1. Navigate to **Extensions > Extension Installer** in the OpenCart admin panel.
2. Click **Upload** and select the NoraGo plugin ZIP file.
3. After the upload, go to **Extensions > Modifications** and click **Refresh**.

### Step 4: Enable the Plugin
1. Navigate to **Extensions > Modules**.
2. Find NoraGo in the list and click **Install**.
3. After the installation, click **Edit** to configure the plugin.

## Configuration

### Configure NoraGo
1. In the OpenCart admin panel, go to **Extensions > Modules** and click **Edit** next to NoraGo.
2. Fill in the necessary information, such as the API key, NoraGo server URL, and other specific settings.
3. Save the settings.

### Test the Connection
After configuring, it is recommended to test the connection to ensure the plugin is communicating correctly with the NoraGo server.

## Usage

### Main Features

* **Data Synchronization:** The plugin automatically synchronizes data from your NoraGo server with your OpenCart store.
* **User Management:** Manage users directly from the OpenCart admin panel.
* **Reports and Analytics:** Access detailed reports on NoraGo's performance and usage through OpenCart.

## Common Issues and Solutions

### Connection Error with Server
* **Description:** Unable to connect to the NoraGo server.
* **Solution:** Verify that the server URL is correct and the API key is valid. Also, check network and firewall settings.

### Data Not Synchronized
* **Description:** Data is not being synchronized correctly.
* **Solution:** Ensure that the cron job is properly configured on your server to run periodic synchronization.

### Compatibility Issues
* **Description:** The plugin is not working correctly after an OpenCart update.
* **Solution:** Check for updates to the NoraGo plugin that are compatible with the new version of OpenCart. Contact the plugin support for additional assistance.

## Contributions
If you wish to contribute to the development of the NoraGo plugin, please submit a pull request to the official repository on GitHub.
