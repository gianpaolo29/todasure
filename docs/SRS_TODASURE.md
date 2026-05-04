# System Requirements Specification (SRS)

## TODASURE: A GPS-Based Digital Fare Meter with Centralized Monitoring System

**Document Version:** 1.0
**Date:** May 3, 2026
**Prepared by:** Systems Analysis & Software Architecture Team
**Project Status:** In Development

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Functional Requirements](#2-functional-requirements)
3. [Non-Functional Requirements](#3-non-functional-requirements)
4. [System Architecture](#4-system-architecture)
5. [Database Design](#5-database-design)
6. [API Specification](#6-api-specification)
7. [User Interface Requirements](#7-user-interface-requirements)
8. [Security Requirements](#8-security-requirements)
9. [Constraints and Assumptions](#9-constraints-and-assumptions)
10. [Glossary](#10-glossary)

---

## 1. System Overview

### 1.1 System Description

**TODASURE** (TODA + Sure Fare) is a GPS-based digital fare computation platform with a centralized web-based monitoring system designed to modernize and regulate tricycle transportation operations in Nasugbu, Batangas. The system eliminates manual fare computation, reduces overcharging disputes, and provides full transparency across all tricycle operations under registered Tricycle Operators and Drivers Associations (TODAs).

The platform integrates real-time GPS tracking from driver mobile devices, automated distance-based fare calculation, and a centralized administrative dashboard for monitoring trips, managing drivers, and handling passenger complaints.

### 1.2 Purpose and Objectives

| Objective | Description |
|-----------|-------------|
| **Accurate Fare Calculation** | Eliminate manual computation by automating fare calculation based on GPS-measured distance, configurable base fares, and per-kilometer rates per barangay. |
| **Reduce Fare Disputes** | Provide transparent, verifiable fare records that both drivers and passengers can reference, reducing overcharging incidents and disagreements. |
| **Improve Monitoring & Transparency** | Enable administrators and barangay officials to monitor active trips in real-time, review trip histories, detect fare violations, and generate performance reports through a centralized dashboard. |
| **Streamline Operations** | Digitize driver registration, tricycle management, TODA organization, and complaint handling into a single integrated platform. |
| **Enforce Compliance** | Automatically detect fare overcharging (>20% threshold), flag suspicious trips, and maintain a formal violation tracking system with severity levels and penalties. |

### 1.3 Target Users

| User Role | Access Type | Description |
|-----------|-------------|-------------|
| **Administrators** | Web Dashboard (Desktop & Mobile Browser) | Municipal or TODA officers who manage the entire system: register drivers, configure fare rates, monitor trips, review complaints, and generate reports. |
| **Drivers** | Mobile Web Interface | Registered tricycle drivers who record trips, view earnings, track their performance, and receive violation alerts. |
| **Passengers** | Public Web Form (via QR Code) | Commuters who are indirect beneficiaries of fair pricing. They can submit complaints through a public QR code-accessible form without requiring an account. |
| **Barangay Officials** | Web Dashboard (Read Access) | Local government personnel who can monitor tricycle operations, view trip data, and review complaints within their jurisdiction. |

### 1.4 Scope

**In Scope:**
- GPS-based real-time fare computation
- Driver, tricycle, and TODA registration & management
- Trip recording with automated fare calculation
- Centralized web-based admin monitoring dashboard
- Fare violation detection and tracking
- Passenger complaint submission and resolution
- Data reporting and analytics
- Mobile-responsive admin monitoring interface
- Barangay-specific fare rate configuration

**Out of Scope:**
- Native mobile application development (uses mobile-responsive web)
- Online payment or e-wallet integration
- Ride-hailing or passenger-to-driver matching
- Vehicle hardware/IoT sensor integration (relies on driver's mobile GPS)
- Multi-municipality deployment (initial release targets Nasugbu, Batangas only)

---

## 2. Functional Requirements

### 2.1 Module A: GPS-Based Fare Computation System

**Purpose:** Automatically compute tricycle fares based on GPS-tracked distance traveled and barangay-specific fare rate configurations.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-A01 | The system shall track the driver's real-time location using the mobile device's GPS sensor via the Geolocation API. | High | Planned |
| FR-A02 | The system shall compute fares using the formula: `fare = base_fare + ((distance_km - base_distance) * per_km_rate)`, where distance exceeds the base distance threshold. | High | Implemented |
| FR-A03 | Fare rates shall be configurable per barangay with the following parameters: `base_fare` (PHP), `base_distance` (km), `per_km_rate` (PHP/km), and `discount_senior` (%). | High | Implemented |
| FR-A04 | The system shall provide Start Trip and End Trip controls for the driver to initiate and terminate GPS tracking. | High | Planned |
| FR-A05 | The system shall display a running fare meter to the driver during an active trip, updating in real-time as distance increases. | High | Planned |
| FR-A06 | The system shall support senior citizen and PWD discount rates (default: 20%) applied to the computed fare. | Medium | Implemented |
| FR-A07 | When actual fare is not provided, the system shall default to the computed fare value. | Medium | Implemented |
| FR-A08 | The system shall store both computed fare and actual fare charged to enable variance analysis. | High | Implemented |

**Fare Calculation Formula:**

```
IF distance_km <= base_distance:
    computed_fare = base_fare
ELSE:
    computed_fare = base_fare + ((distance_km - base_distance) * per_km_rate)

IF senior_discount applies:
    computed_fare = computed_fare * (1 - discount_senior / 100)
```

**Current Fare Rate Configuration (Sample):**

| Barangay | Base Fare (PHP) | Base Distance (km) | Per-KM Rate (PHP) | Senior Discount |
|----------|-----------------|---------------------|-------------------|-----------------|
| Poblacion | 15.00 | 1.00 | 5.00 | 20% |
| San Isidro | 15.00 | 1.00 | 5.50 | 20% |
| Santa Cruz | 12.00 | 0.80 | 5.00 | 20% |

---

### 2.2 Module B: Driver & Tricycle Registration System

**Purpose:** Maintain a centralized registry of all authorized drivers and tricycle units, with assignment tracking and status management.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-B01 | Administrators shall be able to register new driver accounts with the following required fields: `first_name`, `last_name`, `license_number` (unique), `toda_id`, `username`, and `password`. | High | Implemented |
| FR-B02 | The system shall support optional driver fields: `middle_name`, `contact_number`, `address`, and `photo`. | Medium | Implemented |
| FR-B03 | Each driver account shall be linked to a user authentication record with role `driver`. | High | Implemented |
| FR-B04 | Administrators shall be able to register tricycle units with: `plate_number` (unique), `body_number`, `color`, `model`, and assigned `driver_id`. | High | Implemented |
| FR-B05 | Each driver shall be assigned to exactly one TODA organization. | High | Implemented |
| FR-B06 | Driver status shall be manageable with the following states: `active`, `suspended`, `inactive`. | High | Implemented |
| FR-B07 | Tricycle status shall be manageable with the following states: `active`, `inactive`, `maintenance`. | High | Implemented |
| FR-B08 | The system shall support search and filtering of drivers by name, license number, TODA, and status. | Medium | Implemented |
| FR-B09 | Deactivating a driver shall perform a soft delete (status change), preserving historical records. | Medium | Implemented |
| FR-B10 | Driver registration shall be transactional: if user account creation fails, the driver record shall not be created, and vice versa. | High | Implemented |

**Driver Entity Relationships:**

```
User (1) ──── (1) Driver (1) ──── (N) Tricycle
                    │
                    └──── (N:1) ──── TODA (N:1) ──── Barangay
```

---

### 2.3 Module C: Trip Recording System

**Purpose:** Automatically log complete trip records including origin, destination, distance, fare computation, and timestamps for historical analysis and compliance monitoring.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-C01 | The system shall automatically log the following for each trip: `origin`, `destination`, `distance_km`, `computed_fare`, `actual_fare`, `passenger_count`, `started_at`, and `ended_at`. | High | Implemented |
| FR-C02 | Drivers shall only be able to record trips for their own assigned tricycles. Administrators can record trips for any driver. | High | Implemented |
| FR-C03 | The system shall auto-calculate the fare using the active fare rate for the driver's TODA barangay. | High | Implemented |
| FR-C04 | Trip records shall be immutable after completion (status: `completed` or `cancelled`). Only administrators may update trip status. | High | Implemented |
| FR-C05 | The system shall store complete trip history per driver, accessible for reporting and review. | High | Implemented |
| FR-C06 | Trips shall be filterable by `driver_id`, `status`, `date_from`, `date_to` with pagination support (max 100 per page). | Medium | Implemented |
| FR-C07 | Each trip record shall reference the `fare_rate_id` used for computation, enabling audit trail of rate changes. | Medium | Implemented |
| FR-C08 | The system shall automatically detect and flag trips where `actual_fare` exceeds `computed_fare` by more than 20% as potential overcharging violations. | High | Implemented |

**Trip State Diagram:**

```
[Trip Initiated] ──> [In Progress] ──> [Completed]
                                   └──> [Cancelled]
```

**Auto-Violation Detection Logic:**

```
IF actual_fare > (computed_fare * 1.20):
    CREATE violation record (type: 'fare_overcharge', severity: 'minor', status: 'pending')
```

---

### 2.4 Module D: Centralized Monitoring Platform (Web-Based)

**Purpose:** Provide administrators with a comprehensive web-based dashboard for real-time monitoring, management, and oversight of all tricycle operations.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-D01 | The admin dashboard shall display summary statistics: Active Drivers, Total Tricycles, Total TODAs, Trips Today, Revenue Today, Active Trips, Pending Complaints, and Pending Violations. | High | Implemented |
| FR-D02 | The dashboard shall display a table of the 10 most recent trips with driver name, plate number, origin, destination, distance, fare, and timestamp. | High | Implemented |
| FR-D03 | The system shall provide a real-time map view displaying active driver locations using Leaflet.js with OpenStreetMap tiles. | High | Planned |
| FR-D04 | Administrators shall be able to search, filter, and paginate through all trip records. | High | Implemented |
| FR-D05 | The dashboard shall provide sidebar navigation to all management modules: Drivers, Tricycles, Trips, Fare Rates, TODAs, Complaints, and Violations. | High | Implemented |
| FR-D06 | The system shall provide monthly revenue analytics with trip counts for the last 6 months. | Medium | Implemented |
| FR-D07 | Trip records displaying fare variances (overcharging) shall be visually highlighted in red for quick identification. | Medium | Implemented |
| FR-D08 | The monitoring platform shall support TODA management: create, update, and view TODAs with associated barangay and driver counts. | High | Implemented |
| FR-D09 | The monitoring platform shall support barangay management: create, update, and list barangays for fare rate assignment. | Medium | Implemented |

**Dashboard Statistics API Response Structure:**

```json
{
    "total_drivers": 0,
    "total_tricycles": 0,
    "total_todas": 0,
    "trips_today": 0,
    "revenue_today": 0.00,
    "active_trips": 0,
    "pending_complaints": 0,
    "pending_violations": 0,
    "recent_trips": [],
    "monthly_revenue": []
}
```

---

### 2.5 Module E: Fare Violation Monitoring

**Purpose:** Detect, record, and track fare violations to enforce pricing compliance and maintain service standards across all registered tricycle operations.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-E01 | The system shall automatically compare `actual_fare` against `computed_fare` for every completed trip. | High | Implemented |
| FR-E02 | Trips where actual fare exceeds computed fare by more than 20% shall automatically generate a violation record of type `fare_overcharge`. | High | Implemented |
| FR-E03 | The system shall support the following violation types: `fare_overcharge`, `fare_undercharge`, `unauthorized_route`, `complaint_based`, `other`. | High | Implemented |
| FR-E04 | Each violation shall be assigned a severity level: `minor`, `moderate`, or `major`. | High | Implemented |
| FR-E05 | Violations shall follow a status workflow: `pending` -> `confirmed` -> `resolved`, with an alternate path to `appealed`. | High | Implemented |
| FR-E06 | Administrators shall be able to assign penalty descriptions to violations. | Medium | Implemented |
| FR-E07 | Violations shall be filterable by `driver_id`, `status`, and `violation_type`. | Medium | Implemented |
| FR-E08 | The system shall flag suspicious trips with unusually long routes or abnormal distance-to-fare ratios for admin review. | Medium | Planned |
| FR-E09 | The driver dashboard shall display violation alerts when pending or confirmed violations exist. | Medium | Implemented |
| FR-E10 | Violations can be linked to originating `trip_id` and/or `complaint_id` for traceability. | High | Implemented |

**Violation Severity Matrix:**

| Severity | Criteria | Recommended Action |
|----------|----------|--------------------|
| Minor | Fare overcharge 20-30% above computed | Warning |
| Moderate | Fare overcharge 30-50% above computed, repeated minor violations | Suspension review |
| Major | Fare overcharge >50%, multiple complaints, reckless behavior | Suspension/revocation |

**Violation State Diagram:**

```
[Pending] ──> [Confirmed] ──> [Resolved]
    │              │
    │              └──> [Appealed] ──> [Resolved]
    │
    └──> [Resolved] (dismissed)
```

---

### 2.6 Module F: Passenger Complaint System

**Purpose:** Provide a public-facing mechanism for passengers to submit complaints against drivers or tricycle units, with a structured resolution workflow for administrators.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-F01 | The system shall provide a public complaint submission form accessible via QR code scan without requiring user authentication. | High | Implemented |
| FR-F02 | Complaints shall capture: `tricycle_id` (required), `complaint_type` (required), `description` (required), `passenger_name` (optional), `passenger_contact` (optional). | High | Implemented |
| FR-F03 | The system shall support the following complaint types: `overcharging`, `rude_behavior`, `reckless_driving`, `refusal_of_service`, `other`. | High | Implemented |
| FR-F04 | Upon submission, the system shall automatically resolve the `driver_id` from the `tricycle_id` provided. | High | Implemented |
| FR-F05 | Complaints may optionally be linked to a specific `trip_id` for detailed reference. | Medium | Implemented |
| FR-F06 | Complaints shall follow a status workflow: `pending` -> `investigating` -> `resolved` or `dismissed`. | High | Implemented |
| FR-F07 | Administrators shall be able to add internal notes (`admin_notes`) to complaints during review. | Medium | Implemented |
| FR-F08 | During complaint resolution, administrators shall have the option to auto-create a violation record from the complaint with configurable description and severity. | High | Implemented |
| FR-F09 | The system shall display a confirmation with a reference ID upon successful complaint submission. | Medium | Implemented |
| FR-F10 | Complaints shall be filterable by `driver_id` and `status`. | Medium | Implemented |
| FR-F11 | Anonymous complaint submission shall be supported (passenger name defaults to "Anonymous"). | Medium | Implemented |

**Complaint Resolution Workflow:**

```
[Submitted]
    │
    v
[Pending] ──admin review──> [Investigating]
                                  │
                        ┌─────────┴─────────┐
                        v                   v
                  [Resolved]          [Dismissed]
                  (optional:
                  create violation)
```

---

### 2.7 Module G: Data Reporting & Analytics Dashboard

**Purpose:** Generate comprehensive reports and analytics on trip volumes, revenue, violations, and driver performance to support data-driven decision making.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-G01 | The admin dashboard shall display total trip count, total revenue, and violation count as summary KPIs. | High | Implemented |
| FR-G02 | The system shall provide monthly revenue reports with trip counts for the last 6 months. | High | Implemented |
| FR-G03 | The driver dashboard shall display earnings summaries for: today, this week, and this month. | High | Implemented |
| FR-G04 | The driver dashboard shall provide daily earnings breakdown for the last 7 days. | Medium | Implemented |
| FR-G05 | The system shall support time-based analytics filtering: daily, weekly, monthly. | Medium | Partial |
| FR-G06 | Reports shall include driver performance metrics: trip count, total earnings, and violation count per driver. | Medium | Partial |
| FR-G07 | The system shall generate fare compliance reports showing overcharging frequency and severity distribution. | Low | Planned |
| FR-G08 | Revenue reports shall be exportable in printable format. | Low | Planned |
| FR-G09 | The system shall track and display pending complaint and violation counts as operational alerts. | Medium | Implemented |

**Driver Performance API Response:**

```json
{
    "today": { "trip_count": 0, "earnings": 0.00 },
    "this_week": { "trip_count": 0, "earnings": 0.00 },
    "this_month": { "trip_count": 0, "earnings": 0.00 },
    "violations": 0,
    "recent_trips": [],
    "daily_earnings": []
}
```

---

### 2.8 Module H: Mobile Monitoring Interface (Admin)

**Purpose:** Enable administrators and barangay officials to access monitoring capabilities from mobile devices for on-the-ground oversight.

| Req ID | Requirement | Priority | Status |
|--------|-------------|----------|--------|
| FR-H01 | The admin dashboard shall be fully responsive and accessible via mobile browsers (Chrome, Safari) on devices 320px and above. | High | Implemented |
| FR-H02 | The mobile interface shall display the same summary statistics as the desktop dashboard. | High | Implemented |
| FR-H03 | The system shall integrate Leaflet.js with OpenStreetMap for real-time map-based trip monitoring on mobile. | High | Planned |
| FR-H04 | The mobile interface shall display alert badges for pending complaints and violations requiring attention. | Medium | Implemented |
| FR-H05 | Touch-optimized controls shall be provided for navigating between dashboard modules on mobile. | Medium | Implemented |
| FR-H06 | The mobile interface shall support pull-to-refresh or auto-refresh for real-time data updates. | Low | Planned |

---

## 3. Non-Functional Requirements

### 3.1 Performance

| Req ID | Requirement | Target |
|--------|-------------|--------|
| NFR-P01 | API response time for single-record queries | < 500ms |
| NFR-P02 | API response time for list queries with pagination | < 1000ms |
| NFR-P03 | Dashboard initial load time | < 3 seconds |
| NFR-P04 | GPS location update interval during active trips | Every 3-5 seconds |
| NFR-P05 | Maximum concurrent active trips supported | 100+ |
| NFR-P06 | Trip list pagination limit | 100 records per page |

### 3.2 Reliability & Availability

| Req ID | Requirement | Target |
|--------|-------------|--------|
| NFR-R01 | System uptime during operating hours (5:00 AM - 11:00 PM) | 99% |
| NFR-R02 | Database transaction integrity for driver registration | ACID-compliant |
| NFR-R03 | Trip records shall never be lost due to system failure | Durable storage |
| NFR-R04 | Session timeout for inactive users | 30 minutes |

### 3.3 Usability

| Req ID | Requirement |
|--------|-------------|
| NFR-U01 | The driver interface shall be operable with one hand on a mobile device. |
| NFR-U02 | The complaint form shall be completable in under 2 minutes by a first-time user. |
| NFR-U03 | All forms shall provide real-time validation feedback with clear error messages. |
| NFR-U04 | The system shall use SweetAlert2 for consistent, non-intrusive user notifications. |
| NFR-U05 | All monetary values shall be displayed in Philippine Peso (PHP) format. |
| NFR-U06 | All timestamps shall display in Philippine Time (UTC+8) using PH locale formatting. |

### 3.4 Scalability

| Req ID | Requirement |
|--------|-------------|
| NFR-S01 | The database schema shall support multiple barangays with independent fare rate configurations. |
| NFR-S02 | The system shall support addition of new TODAs and barangays without code changes. |
| NFR-S03 | API endpoints shall support pagination to handle growing data volumes. |

### 3.5 Compatibility

| Req ID | Requirement |
|--------|-------------|
| NFR-C01 | The web application shall be compatible with Chrome 90+, Firefox 90+, Safari 14+, and Edge 90+. |
| NFR-C02 | The mobile interface shall be compatible with Android 10+ and iOS 14+ mobile browsers. |
| NFR-C03 | Form inputs on mobile shall use `font-size: 16px` minimum to prevent iOS auto-zoom. |
| NFR-C04 | The system shall function on XAMPP (Apache + MySQL) for local deployment. |

---

## 4. System Architecture

### 4.1 Architecture Pattern

The system follows a **Client-Server** architecture with a **RESTful API** backend pattern:

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                             │
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │
│  │ Admin Portal │  │Driver Mobile │  │ Passenger Complaint│    │
│  │  (Desktop/   │  │  Interface   │  │   Form (Public)    │    │
│  │   Mobile)    │  │              │  │                    │    │
│  └──────┬───────┘  └──────┬───────┘  └─────────┬──────────┘    │
│         │                 │                     │               │
└─────────┼─────────────────┼─────────────────────┼───────────────┘
          │     HTTPS/JSON  │                     │
          v                 v                     v
┌─────────────────────────────────────────────────────────────────┐
│                        API LAYER                                │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                  PHP REST API (Apache)                    │   │
│  │                                                          │   │
│  │  /api/auth/*        Authentication & Sessions            │   │
│  │  /api/drivers/*     Driver CRUD                          │   │
│  │  /api/tricycles/*   Tricycle CRUD                        │   │
│  │  /api/trips/*       Trip Recording & Queries             │   │
│  │  /api/fares/*       Fare Rate Configuration              │   │
│  │  /api/complaints/*  Complaint Submission & Management    │   │
│  │  /api/violations/*  Violation Tracking                   │   │
│  │  /api/toda/*        TODA Management                      │   │
│  │  /api/barangays/*   Barangay Management                  │   │
│  │  /api/dashboard/*   Statistics & Analytics               │   │
│  └──────────────────────────┬───────────────────────────────┘   │
│                             │                                   │
└─────────────────────────────┼───────────────────────────────────┘
                              │  PDO/MySQL
                              v
┌─────────────────────────────────────────────────────────────────┐
│                      DATA LAYER                                 │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              MySQL Database (todasure_db)                │   │
│  │                                                          │   │
│  │  users | drivers | tricycles | trips | fare_rates        │   │
│  │  todas | barangays | complaints | violations             │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) | - |
| **UI Framework** | Custom CSS Design System | - |
| **Icons** | Font Awesome | 6.5.0 |
| **Notifications** | SweetAlert2 | 11.x |
| **Maps** | Leaflet.js + OpenStreetMap | Planned |
| **Fonts** | Inter, Space Grotesk (Google Fonts) | - |
| **Backend** | PHP | 8.x |
| **Database** | MySQL (InnoDB Engine) | 8.x |
| **Web Server** | Apache (XAMPP) | 2.4.x |
| **Authentication** | PHP Sessions + password_hash (bcrypt) | - |

### 4.3 Directory Structure

```
/TodaShare/
├── api/                          # RESTful API endpoints
│   ├── config/
│   │   ├── database.php          # PDO connection configuration
│   │   └── helpers.php           # Auth, fare calc, response utilities
│   ├── auth/
│   │   ├── login.php             # POST - User authentication
│   │   ├── register.php          # POST - User registration
│   │   ├── logout.php            # POST - Session destruction
│   │   └── session.php           # GET  - Session validation
│   ├── drivers/index.php         # GET/POST/PUT/DELETE - Driver CRUD
│   ├── tricycles/index.php       # GET/POST/PUT - Tricycle CRUD
│   ├── trips/index.php           # GET/POST/PUT - Trip management
│   ├── fares/index.php           # GET/POST/PUT - Fare rate config
│   ├── complaints/index.php      # GET/POST/PUT - Complaint handling
│   ├── violations/index.php      # GET/POST/PUT - Violation tracking
│   ├── toda/index.php            # GET/POST/PUT - TODA management
│   ├── barangays/index.php       # GET/POST/PUT - Barangay management
│   └── dashboard/
│       ├── stats.php             # GET - Admin dashboard statistics
│       └── driver-stats.php      # GET - Driver performance stats
├── admin/                        # Admin web portal
│   ├── dashboard.html            # Main admin dashboard
│   ├── login.html                # Admin authentication
│   ├── drivers.html              # Driver management
│   ├── tricycles.html            # Tricycle management
│   ├── trips.html                # Trip monitoring
│   ├── fares.html                # Fare rate configuration
│   ├── toda.html                 # TODA management
│   ├── complaints.html           # Complaint review
│   └── violations.html           # Violation tracking
├── driver/                       # Driver mobile interface
│   ├── dashboard.html            # Driver dashboard & trip recording
│   └── login.html                # Driver authentication
├── passenger/                    # Passenger-facing pages
│   ├── dashboard.html            # Passenger home
│   ├── login.html                # Passenger authentication
│   └── complaint.html            # Public complaint form
├── assets/
│   ├── css/style.css             # Global design system
│   ├── js/api.js                 # API client library
│   └── img/                      # Static assets
├── database/
│   ├── schema.sql                # Database schema & seed data
│   └── migrate_auth.sql          # Auth migration script
├── index.html                    # Landing page
├── login.html                    # Unified login
└── signup.html                   # User registration
```

---

## 5. Database Design

### 5.1 Entity-Relationship Diagram

```
                            ┌──────────────┐
                            │   BARANGAYS   │
                            │──────────────│
                            │ id (PK)      │
                            │ name         │
                            │ municipality │
                            │ province     │
                            └──────┬───────┘
                                   │ 1
                                   │
                         ┌─────────┴─────────┐
                         │                   │
                    N    │              N    │
              ┌──────────┴──┐       ┌───────┴────────┐
              │    TODAS    │       │   FARE_RATES   │
              │─────────────│       │────────────────│
              │ id (PK)     │       │ id (PK)        │
              │ name        │       │ barangay_id(FK)│
              │ barangay_id │       │ base_fare      │
              │ president   │       │ base_distance  │
              │ contact     │       │ per_km_rate    │
              │ status      │       │ discount_senior│
              └──────┬──────┘       │ effective_date │
                     │ 1            │ status         │
                     │              └────────┬───────┘
                N    │                       │
              ┌──────┴──────┐                │
              │   DRIVERS   │                │
              │─────────────│                │
              │ id (PK)     │                │
              │ user_id(FK) ├──┐             │
              │ toda_id(FK) │  │             │
              │ license_no  │  │             │
              │ status      │  │             │
              └──┬──────┬───┘  │             │
                 │ 1    │ 1    │ 1           │
                 │      │     ┌┴──────────┐  │
            N    │      │     │   USERS   │  │
       ┌────────┴┐     │     │───────────│  │
       │TRICYCLES│     │     │ id (PK)   │  │
       │─────────│     │     │ username  │  │
       │ id (PK) │     │     │ email     │  │
       │ driver  │     │     │ password  │  │
       │ plate_no│     │     │ role      │  │
       │ body_no │     │     │ status    │  │
       │ status  │     │     └───────────┘  │
       └────┬────┘     │                    │
            │ 1        │ N                  │ 1
            │     ┌────┴──────────┐         │
       N    │     │    TRIPS      │─────────┘
  ┌─────────┤     │──────────────│
  │         │     │ id (PK)      │
  │         │     │ tricycle_id  │
  │         │     │ driver_id    │
  │         │     │ fare_rate_id │
  │         │     │ origin       │
  │         │     │ destination  │
  │         │     │ distance_km  │
  │         │     │ computed_fare│
  │         │     │ actual_fare  │
  │         │     │ status       │
  │         │     └──────┬───────┘
  │         │            │ 1
  │         │            │
  │    N    │       N    │
  │  ┌──────┴────┐ ┌────┴──────────┐
  │  │COMPLAINTS │ │  VIOLATIONS   │
  │  │───────────│ │──────────────│
  └──┤ id (PK)   │ │ id (PK)      │
     │ tricycle_id│ │ driver_id    │
     │ driver_id │ │ trip_id      │
     │ trip_id   │ │ complaint_id │
     │ type      │ │ type         │
     │ status    │ │ severity     │
     │ admin_note│ │ penalty      │
     └──────────┘ │ status       │
                  └──────────────┘
```

### 5.2 Table Specifications

#### 5.2.1 Users

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique user identifier |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Login username |
| email | VARCHAR(100) | UNIQUE, NOT NULL | User email address |
| password | VARCHAR(255) | NOT NULL | Bcrypt-hashed password |
| first_name | VARCHAR(50) | NOT NULL | User first name |
| last_name | VARCHAR(50) | NOT NULL | User last name |
| phone | VARCHAR(20) | NULL | Contact number |
| role | ENUM | NOT NULL, DEFAULT 'resident' | Values: `admin`, `driver`, `resident` |
| status | ENUM | NOT NULL, DEFAULT 'active' | Values: `active`, `inactive`, `pending` |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

#### 5.2.2 Drivers

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique driver identifier |
| user_id | INT | FK -> users(id) CASCADE | Linked user account |
| toda_id | INT | FK -> todas(id) RESTRICT | Assigned TODA |
| first_name | VARCHAR(50) | NOT NULL | Driver first name |
| last_name | VARCHAR(50) | NOT NULL | Driver last name |
| middle_name | VARCHAR(50) | NULL | Driver middle name |
| license_number | VARCHAR(50) | UNIQUE, NOT NULL | Driver's license ID |
| contact_number | VARCHAR(20) | NULL | Phone number |
| address | TEXT | NULL | Home address |
| photo | VARCHAR(255) | NULL | Profile photo path |
| status | ENUM | DEFAULT 'active' | Values: `active`, `suspended`, `inactive` |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

#### 5.2.3 Tricycles

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique tricycle identifier |
| driver_id | INT | FK -> drivers(id) RESTRICT | Assigned driver |
| plate_number | VARCHAR(20) | UNIQUE, NOT NULL | Vehicle plate number |
| body_number | VARCHAR(20) | NOT NULL | Tricycle body number |
| color | VARCHAR(30) | NULL | Vehicle color |
| model | VARCHAR(50) | NULL | Vehicle model/make |
| status | ENUM | DEFAULT 'active' | Values: `active`, `inactive`, `maintenance` |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

#### 5.2.4 Trips

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique trip identifier |
| tricycle_id | INT | FK -> tricycles(id) RESTRICT | Tricycle used |
| driver_id | INT | FK -> drivers(id) RESTRICT | Driver on trip |
| fare_rate_id | INT | FK -> fare_rates(id) SET NULL | Fare rate applied |
| origin | VARCHAR(255) | NOT NULL | Start location |
| destination | VARCHAR(255) | NOT NULL | End location |
| distance_km | DECIMAL(10,2) | NOT NULL | Distance traveled |
| computed_fare | DECIMAL(10,2) | NOT NULL | System-calculated fare |
| actual_fare | DECIMAL(10,2) | NULL | Fare actually charged |
| passenger_count | INT | DEFAULT 1 | Number of passengers |
| status | ENUM | DEFAULT 'completed' | Values: `completed`, `cancelled` |
| started_at | TIMESTAMP | NULL | Trip start time |
| ended_at | TIMESTAMP | NULL | Trip end time |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

#### 5.2.5 Fare Rates

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique rate identifier |
| barangay_id | INT | FK -> barangays(id) RESTRICT | Associated barangay |
| base_fare | DECIMAL(10,2) | NOT NULL | Minimum fare (PHP) |
| base_distance | DECIMAL(10,2) | NOT NULL | Distance covered by base fare (km) |
| per_km_rate | DECIMAL(10,2) | NOT NULL | Additional charge per km (PHP) |
| discount_senior | DECIMAL(5,2) | DEFAULT 20.00 | Senior/PWD discount percentage |
| effective_date | DATE | NOT NULL | Rate effective date |
| status | ENUM | DEFAULT 'active' | Values: `active`, `inactive` |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

#### 5.2.6 Complaints

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique complaint identifier |
| tricycle_id | INT | FK -> tricycles(id) | Reported tricycle |
| driver_id | INT | FK -> drivers(id) | Reported driver |
| trip_id | INT | FK -> trips(id) SET NULL | Associated trip |
| passenger_name | VARCHAR(100) | DEFAULT 'Anonymous' | Complainant name |
| passenger_contact | VARCHAR(50) | NULL | Complainant contact |
| complaint_type | ENUM | NOT NULL | Values: `overcharging`, `rude_behavior`, `reckless_driving`, `refusal_of_service`, `other` |
| description | TEXT | NOT NULL | Complaint details |
| status | ENUM | DEFAULT 'pending' | Values: `pending`, `investigating`, `resolved`, `dismissed` |
| admin_notes | TEXT | NULL | Internal admin notes |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Submission time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update time |

#### 5.2.7 Violations

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PK, AUTO_INCREMENT | Unique violation identifier |
| driver_id | INT | FK -> drivers(id) RESTRICT | Violating driver |
| trip_id | INT | FK -> trips(id) SET NULL | Related trip |
| complaint_id | INT | FK -> complaints(id) SET NULL | Source complaint |
| violation_type | ENUM | NOT NULL | Values: `fare_overcharge`, `fare_undercharge`, `unauthorized_route`, `complaint_based`, `other` |
| description | TEXT | NULL | Violation details |
| severity | ENUM | DEFAULT 'minor' | Values: `minor`, `moderate`, `major` |
| penalty | VARCHAR(255) | NULL | Assigned penalty |
| status | ENUM | DEFAULT 'pending' | Values: `pending`, `confirmed`, `appealed`, `resolved` |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update time |

---

## 6. API Specification

### 6.1 Base URL

```
http://localhost/TodaShare/api/
```

### 6.2 Authentication

All protected endpoints require an active PHP session. Authentication is performed via `POST /api/auth/login.php`.

| Header | Value |
|--------|-------|
| Content-Type | application/json |
| Cookie | PHPSESSID=<session_id> |

### 6.3 Response Format

All API responses follow a consistent JSON structure:

**Success Response:**
```json
{
    "data": { ... },
    "message": "Operation successful"
}
```

**Error Response:**
```json
{
    "error": "Error description",
    "message": "Detailed error message"
}
```

### 6.4 Endpoint Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login.php` | Public | User login, returns role-based redirect |
| POST | `/auth/register.php` | Public | User registration (resident role) |
| POST | `/auth/logout.php` | Required | Destroy session |
| GET | `/auth/session.php` | Required | Validate session, get user info |
| GET | `/drivers/index.php` | Required | List/search drivers |
| POST | `/drivers/index.php` | Admin | Create driver + user account |
| PUT | `/drivers/index.php?id=N` | Admin | Update driver record |
| DELETE | `/drivers/index.php?id=N` | Admin | Soft-delete driver |
| GET | `/tricycles/index.php` | Required | List tricycles |
| POST | `/tricycles/index.php` | Admin | Register tricycle |
| PUT | `/tricycles/index.php?id=N` | Admin | Update tricycle |
| GET | `/trips/index.php` | Required | List/filter trips |
| POST | `/trips/index.php` | Driver/Admin | Record new trip |
| PUT | `/trips/index.php?id=N` | Admin | Update trip |
| GET | `/fares/index.php` | Required | List fare rates |
| POST | `/fares/index.php` | Admin | Create fare rate |
| PUT | `/fares/index.php?id=N` | Admin | Update fare rate |
| GET | `/complaints/index.php` | Required | List complaints |
| POST | `/complaints/index.php` | Public | Submit complaint (QR) |
| PUT | `/complaints/index.php?id=N` | Admin | Update complaint status |
| GET | `/violations/index.php` | Required | List violations |
| POST | `/violations/index.php` | Admin | Create violation |
| PUT | `/violations/index.php?id=N` | Admin | Update violation |
| GET | `/toda/index.php` | Required | List TODAs |
| POST | `/toda/index.php` | Admin | Create TODA |
| PUT | `/toda/index.php?id=N` | Admin | Update TODA |
| GET | `/barangays/index.php` | Required | List barangays |
| POST | `/barangays/index.php` | Admin | Create barangay |
| PUT | `/barangays/index.php?id=N` | Admin | Update barangay |
| GET | `/dashboard/stats.php` | Admin | Admin dashboard KPIs |
| GET | `/dashboard/driver-stats.php` | Driver | Driver performance stats |

---

## 7. User Interface Requirements

### 7.1 Design System

| Property | Value |
|----------|-------|
| **Primary Background** | `#050816` (Deep Space) |
| **Card Background** | `rgba(12, 16, 36, 0.7)` with `backdrop-filter: blur(16px)` |
| **Primary Accent** | `#00e6a0` (Emerald Green) |
| **Secondary Accent** | `#0ea5e9` (Sky Blue) |
| **Tertiary Accent** | `#8b5cf6` (Violet) |
| **Text Primary** | `#f1f5f9` |
| **Text Secondary** | `#94a3b8` |
| **Text Muted** | `#64748b` |
| **Font Display** | Space Grotesk (700) |
| **Font Body** | Inter (300-800) |
| **Border Radius Cards** | 20px |
| **Border Radius Inputs** | 12px |
| **Border Radius Buttons** | 12px |

### 7.2 Responsive Breakpoints

| Breakpoint | Target | Adjustments |
|------------|--------|-------------|
| > 1024px | Desktop | Full layout, sidebar navigation |
| 768px - 1024px | Tablet | Collapsible sidebar, stacked cards |
| 480px - 768px | Mobile | Single column, hamburger menu |
| 320px - 480px | Small Mobile | Compressed padding, stacked forms |
| < 360px | Minimum | Tight spacing, essential content only |

### 7.3 Page Specifications

| Page | Viewport | Key Components |
|------|----------|----------------|
| Landing Page | All | Animated orb background, rotating logo ring, feature cards, CTA buttons |
| Login | All | Glassmorphism card, email/password fields, password toggle, loading state |
| Registration | All | Glassmorphism card, 4 fields, password strength meter, confirm password |
| Admin Dashboard | Desktop/Tablet | 8 stat cards, recent trips table, sidebar navigation |
| Driver Dashboard | Mobile-first | Earnings cards, trip recording form, recent trips |
| Complaint Form | Mobile-first | Public form, tricycle ID input, type selector, description |

---

## 8. Security Requirements

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| SR-01 | Passwords shall be hashed using bcrypt before storage. | `password_hash()` with PASSWORD_DEFAULT |
| SR-02 | All API endpoints (except public) shall verify active session before processing. | `requireAuth()` helper function |
| SR-03 | Admin-only endpoints shall verify user role before processing. | `requireAdmin()` helper function |
| SR-04 | All user inputs shall be parameterized in database queries to prevent SQL injection. | PDO prepared statements throughout |
| SR-05 | API responses shall never expose password hashes or internal system details. | Selective field returns |
| SR-06 | Session data shall be destroyed on logout. | `session_destroy()` in logout.php |
| SR-07 | Driver registration shall use database transactions to maintain data integrity. | PDO `beginTransaction()`/`commit()`/`rollback()` |
| SR-08 | Public complaint form shall not require authentication but shall validate required fields. | Server-side validation |
| SR-09 | Cross-Origin Resource Sharing (CORS) headers shall be configured for API security. | Apache/PHP header configuration |
| SR-10 | Inactive user accounts (status != 'active') shall be denied login access. | Status check in login.php |

---

## 9. Constraints and Assumptions

### 9.1 Constraints

| ID | Constraint |
|----|-----------|
| CON-01 | The system is deployed on XAMPP (Apache + MySQL) for the initial release, limiting deployment to local or single-server environments. |
| CON-02 | GPS accuracy depends on the driver's mobile device hardware and network connectivity; no additional IoT hardware is used in the initial version. |
| CON-03 | The system does not support real-time payment processing or e-wallet integration. |
| CON-04 | Trip recording currently requires manual input of origin and destination as text strings; GPS auto-fill is planned for a future iteration. |
| CON-05 | The system targets Nasugbu, Batangas only for the initial deployment. |
| CON-06 | No native mobile applications are developed; all interfaces are delivered via responsive web pages. |

### 9.2 Assumptions

| ID | Assumption |
|----|-----------|
| ASM-01 | Drivers have access to smartphones with GPS capability and mobile data connectivity. |
| ASM-02 | The municipal or TODA administration will assign a system administrator to manage the platform. |
| ASM-03 | Barangay-level fare rates are pre-configured by administrators before the system goes live. |
| ASM-04 | Tricycle body numbers and plate numbers are unique and verifiable. |
| ASM-05 | QR codes linking to the complaint form will be physically displayed on registered tricycles. |
| ASM-06 | Users have basic literacy in operating web-based applications on mobile devices. |
| ASM-07 | A stable local network or internet connection is available for accessing the web platform. |

### 9.3 Dependencies

| ID | Dependency |
|----|-----------|
| DEP-01 | Google Fonts CDN for Inter and Space Grotesk typefaces. |
| DEP-02 | Font Awesome CDN (v6.5.0) for iconography. |
| DEP-03 | SweetAlert2 CDN (v11.x) for UI notifications. |
| DEP-04 | Leaflet.js + OpenStreetMap (planned) for map-based monitoring. |
| DEP-05 | Browser Geolocation API for GPS-based fare computation. |

---

## 10. Glossary

| Term | Definition |
|------|-----------|
| **TODA** | Tricycle Operators and Drivers Association -- a local organization that manages tricycle operations within a barangay. |
| **Barangay** | The smallest administrative division in the Philippines, equivalent to a village or district. |
| **Fare Rate** | A configurable pricing structure consisting of a base fare, base distance, and per-kilometer rate specific to a barangay. |
| **Computed Fare** | The fare amount automatically calculated by the system based on distance traveled and the applicable fare rate. |
| **Actual Fare** | The fare amount actually charged to the passenger by the driver, which may differ from the computed fare. |
| **Fare Overcharge** | A violation detected when the actual fare exceeds the computed fare by more than 20%. |
| **Body Number** | A unique identification number displayed on the tricycle body, assigned by the TODA for identification purposes. |
| **QR Code Complaint** | A system feature allowing passengers to scan a QR code on a tricycle to access the public complaint submission form. |
| **GPS Fare Meter** | The real-time fare computation feature that tracks distance via GPS and calculates fare continuously during a trip. |
| **Senior/PWD Discount** | A mandatory fare discount (default 20%) applied to senior citizens and persons with disabilities as mandated by Philippine law. |
| **KPI** | Key Performance Indicator -- summary metrics displayed on dashboards (e.g., trips today, revenue today). |
| **Soft Delete** | A data management approach where records are marked as inactive rather than permanently removed, preserving historical data. |
| **CRUD** | Create, Read, Update, Delete -- the four basic operations of persistent storage. |

---

**End of Document**

*This SRS is a living document and will be updated as the system evolves through development iterations.*
