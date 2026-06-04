# 1. Introduction

PharmaCore is a web based Medicine Inventory Management System designed to automate the day to day operations of pharmacies. There are many small to medium pharmacies that still use notebooks or spreadsheets to track their inventory. This manual method is time consuming, error prone and inefficient especially in the case of large number of medicines.  
  
The proposed system will give a centralized way of managing medicine information, stock quantities, expiry date tracking, and inventory alerts. The system will help pharmacists keep accurate inventory records, reduce wastage, improve operational efficiency and enable better decision-making.  
  
The application will be developed as a web based system using PHP, MySQL, HTML, CSS and JavaScript. It will be running on a local using XAMPP and can later be deployed online for real-world use.

# 2. Problem Statement

Many pharmacies in Nepal still rely on manual record-keeping systems to manage inventory and sales. These traditional methods create several operational problems:

- Difficulty in maintaining accurate stock records.
- Risk of selling expired medicines.
- Lack of timely information on low-stock.
- Manual management of medicine records is time-consuming and prone to errors.

# 3. Objectives

## 3.1 General Objective

To design and develop a web-based Medicine Inventory Management System for efficient inventory control, stock monitoring, and expiry management.

## 3.2 Specific Objectives

- To maintain accurate medicine inventory records through a computerized system.
- To automate stock quantity monitoring and inventory updates.
- To monitor medicine expiry dates and generate alerts for near-expiry and expired medicines.
- To provide low-stock alerts when medicine quantity falls below the reorder level.

# 4. Methodology

## 4.1.1 Introduction
  
The software development model for developing the Pharmacy Inventory Management System is the **Incremental Software Development Model.** The project is divided into manageable modules such as authentication, category management, medicine management, stock management and alert generation. Each module will be designed, implemented and tested before going to the next module. This approach was chosen because it permits constant improvement and addition of supervisor’s comments after each increment without breaking the whole system. The result is reduced development risks and improved software quality by early detection of errors . Early functional versions of the system allow tracking progress towards project goals .

![[Incremental-Model.png]]

## 4.1.2 Study of Existing System

The existing system used by many pharmacies is mostly manual, with a lot of notebooks or simple spreadsheets to store medicine information and manually update the stock levels. Even critical tasks like checking expiry dates of medicines and compiling performance reports are done manually.

This traditional approach has major limitations: it is extremely time consuming, error-prone and makes searching through historical records incredibly difficult. Furthermore, without automated alerts, expiry dates and low-stock situations are often overlooked, increasing the risk of medicine wastage and inventory shortages. Finally, the use of physical logs or unprotected files results in poor data security and the pharmacy will not have a reliable audit trail to track changes or correct discrepancies.

## 4.1.3 Requirement Collection

## Functional Requirements

![[Pasted image 20260604170839.png|368]]

fig. Use-case diagram

- User should be able to login and logout.
- User should be able to add, edit, delete and search medicine.
- User should be able to manage medicine categories.
- User should be able to update medicine stock quantities.
- User should be able to track expiry dates.
- User should be able to track stock quantity.

- System should update stock automatically.
- System should generate low stock alerts.
- System should generate expiring soon and expired alerts.

### Non-Functional Requirements

- Interface should be user-friendly.
- System should have fast response time.
- System should have secure authentication.
- System should maintain data integrity.
- System should be maintainable.
- System should be scalable.

# 4.2 Feasibility Study

### **4.2.1 Technical Feasibility**

The project demonstrates high technical feasibility as it relies entirely on open-source, widely documented web technologies including PHP (version 8+), MySQL, and JavaScript. Development can be conducted locally using the standard XAMPP stack. Furthermore, the developer has access to sufficient documentation and community learning resources to resolve implementation challenges. The system’s infrastructure demands are minimal, requiring only a modern web browser and basic development software (such as VS Code or IntelliJ IDEA with Git for version control) running on a computer with at least 8 GB of RAM and 10 GB of free storage space.

## 4.2.2 Operational Feasibility
  
The project is operationally feasible as it addresses key functional challenges of daily pharmacy workflows, through an intuitive and user-friendly interface that requires minimal staff training. The system automates inventory management tasks such as maintaining medicine records, tracking stock quantities, and monitoring medicine expiry dates.
This reduces manual work and minimizes human error.Moreover, the automated alert system actively notifies low stock levels and nearing expiry medicines thus directly preventing stock-outs and minimizing monetary losses through expired inventory. This means it can be seamlessly added to existing pharmacy environments, with high user adoption and immediate operational efficiency.

### 4.2.3 Economic Feasibility

The project is economically feasible because all tools used are free and open source.

# 4.3 High Level Design of System
## 4.3.1 System Flow Chart

![[Pasted image 20260604171312.png|497]]

<!--## 4.3.2 Methodology of the Proposed System -->


## 4.3.2 Working Mechanism of the Proposed System

1. Users log into the system securely using authenticated credentials.
2. Users can add, edit, delete, and search medicine records.
3. Users can organize medicines using categories.
4. Users can update medicine stock quantities whenever inventory changes occur.
5. The system continuously monitors stock levels and generates low-stock alerts when medicines fall below a predefined threshold.
6. The system monitors expiry dates and automatically identifies near-expiry and expired medicines.
7. Users can view current inventory information through a centralized dashboard.

# 5. Gantt Chart

![[Pasted image 20260604171651.png]]

# 6. Expected Outcome
  
It will provide a secure and user-friendly web application for managing medicine inventory, monitoring stock levels, and tracking medicine expiry dates.
  
Final results include:  
- **Accurate Inventory Management:** Maintains organized and up-to-date medicine records.
- **Automated Stock Monitoring:** Helps prevent inventory shortages through low-stock alerts.
- **Expiry Date Tracking:** Reduces medicine wastage by identifying near-expiry and expired medicines.
- **Improved Operational Efficiency:** Reduces manual record-keeping and minimizes human errors.
# 7. References

1. Design https://www.figma.com/
2. HTML https://www.w3schools.com/Html/
3. CSS https://www.w3schools.com/css/
4. Javascript https://www.w3schools.com/js/
5. PHP Official Documentation https://www.php.net/docs.php
6. MySQL Reference Manual https://dev.mysql.com/doc/
7. XAMPP https://www.apachefriends.org/
8. MDN Web Docs https://developer.mozilla.org/
9. ER Diagram & Flowchart https://app.diagrams.net/