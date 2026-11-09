# AWS EC2 to RDS MySQL Integration with Classic Load Balancer, Route 53 and ACM

## Project Overview

This project demonstrates how to deploy a PHP-based web application on Amazon EC2 and connect it to an Amazon RDS MySQL database.

The application allows users to submit their name, email address and phone number through a web form. The submitted information is stored in RDS MySQL and the latest records are displayed on the application webpage.

The application is accessed through a custom domain with HTTPS enabled using AWS Certificate Manager (ACM). An internet-facing Classic Load Balancer distributes incoming application traffic to two EC2 application servers.

**Live Application:** https://www.vaibhavcloud.site

## Architecture

Browser → Route 53 → Classic Load Balancer → EC2 Application Servers → Amazon RDS MySQL

HTTPS is terminated at the load balancer using an ACM certificate. The load balancer forwards HTTP traffic to the EC2 instances.

## AWS Services Used

| Service | Purpose |
|---|---|
| Amazon VPC | Provides the networking environment |
| Amazon EC2 | Hosts the PHP application |
| Amazon RDS MySQL | Stores application records |
| Classic Load Balancer | Distributes incoming application traffic |
| AWS Certificate Manager | Provides the SSL/TLS certificate |
| Amazon Route 53 | Maps the custom domain to the load balancer |
| Security Groups | Controls network access between resources |

## Application Features

- PHP-based web application hosted on EC2.
- Form to submit name, email ID and phone number.
- MySQL database connectivity through Amazon RDS.
- Stores submitted records in the database.
- Displays the latest database records.
- Two EC2 application servers registered with the load balancer.
- HTTPS access using an ACM-issued certificate.
- Custom domain configured through Route 53.

## Implementation

### 1. Networking Configuration
- Used an existing Amazon VPC.
- Configured subnets across multiple Availability Zones.
- Configured networking for the load balancer and RDS subnet group.

### 2. Amazon RDS MySQL
- Created an Amazon RDS MySQL database instance.
- Configured database connectivity on port 3306.
- Kept the database non-publicly accessible.
- Configured the database security group to allow application access.

### 3. EC2 Application Servers
- Launched two Amazon Linux application servers.
- Installed Apache HTTP Server and PHP.
- Deployed the PHP application on both instances.
- Configured database connectivity from the application servers.

### 4. Classic Load Balancer
- Created an internet-facing Classic Load Balancer.
- Registered both EC2 application servers.
- Configured HTTPS listener on port 443.
- Forwarded traffic to the application instances over HTTP port 80.
- Verified that both registered instances were In-Service.

### 5. SSL/TLS with ACM
- Requested an SSL/TLS certificate through AWS Certificate Manager.
- Completed domain validation.
- Attached the issued certificate to the load balancer's HTTPS listener.

### 6. Route 53
- Created a public hosted zone for the domain.
- Configured an Alias A record for the `www` subdomain.
- Routed domain traffic to the Classic Load Balancer.

## Screenshots

### RDS Configuration
![RDS MySQL Configuration](screenshots/rds-mysql-configuration.png)

![RDS Networking Configuration](screenshots/rds-networking-configuration.png)

### Application and Database Integration
![Application and RDS Integration](screenshots/application-rds-integration.png)

### SSL Certificate
![ACM SSL Certificate](screenshots/acm-ssl-certificate.png)

### Application Working
![Application Working](screenshots/poc-application-working.png)

### Load Balancer
![Classic Load Balancer Overview](screenshots/classic-load-balancer-overview.png)

### EC2 Application Servers
![EC2 Application Servers](screenshots/ec2-application-servers.png)

### VPC and Subnets
![VPC Subnet Configuration](screenshots/vpc-subnet-configuration.png)

## Security Considerations

- RDS is configured as non-publicly accessible.
- Database access should be allowed only from the application server security group.
- SSH access should be restricted to the administrator's IP address.
- HTTPS is enabled through ACM on the load balancer.
- Database credentials must not be committed to GitHub.
- Use environment variables or a secure secrets-management service for sensitive configuration.

## Key Learnings

- Deploying PHP applications on Amazon Linux.
- Connecting EC2 applications to Amazon RDS MySQL.
- Configuring security groups for application and database communication.
- Configuring a Classic Load Balancer and registering EC2 instances.
- Enabling HTTPS using AWS Certificate Manager.
- Configuring custom DNS records using Route 53.
- Troubleshooting application and database connectivity.

## Project Status

Proof of concept successfully demonstrated with application data insertion and retrieval from Amazon RDS MySQL.

## Author

**Vaibhav Mule**

GitHub: https://github.com/vaibhavmule-code

LinkedIn: https://www.linkedin.com/in/vaibhav-d-mule/