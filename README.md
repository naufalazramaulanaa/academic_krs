# Academic KRS — Enrollment Management System

A full-stack Academic KRS (Kartu Rencana Studi) management system designed to manage student course enrollments with server-side pagination, advanced filtering, multi-column sorting, CRUD operations, PostgreSQL optimization, and large-scale CSV export.

The system is designed and tested to handle an enrollment dataset of up to **5 million records** while keeping the application responsive for normal browsing, searching, filtering, and sorting operations.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [Database Design](#database-design)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Installation](#installation)
  - [Backend Installation](#backend-installation)
  - [Database Configuration](#database-configuration)
  - [Frontend Installation](#frontend-installation)
- [Environment Variables](#environment-variables)
- [Running the Application](#running-the-application)
- [Database Seeding](#database-seeding)
- [Large Dataset — 5 Million Records](#large-dataset--5-million-records)
- [API Documentation](#api-documentation)
- [Enrollment API](#enrollment-api)
- [Advanced Filtering](#advanced-filtering)
- [Advanced Sorting](#advanced-sorting)
- [Search](#search)
- [Pagination](#pagination)
- [CSV Export](#csv-export)
- [Performance Optimization](#performance-optimization)
- [Data Integrity](#data-integrity)
- [Validation](#validation)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Development Workflow](#development-workflow)
- [Production Deployment](#production-deployment)
- [Security Considerations](#security-considerations)
- [Known Limitations](#known-limitations)
- [Future Improvements](#future-improvements)
- [License](#license)

---

# Overview

Academic KRS is a web-based enrollment management application for managing student course registrations.

The application provides a centralized interface for:

- Browsing enrollments
- Searching students and courses
- Filtering enrollment records
- Advanced multi-condition filtering
- Multi-column sorting
- Creating enrollment records
- Updating enrollment records
- Deleting enrollment records
- Paginating large datasets
- Exporting enrollment data to CSV

The backend is implemented using Laravel and PostgreSQL, while the frontend is implemented using Next.js.

A major design goal of this project is to demonstrate how a conventional CRUD application can be designed to remain usable when working with a large relational dataset.

The system has been tested with a dataset containing:

> **5,000,000 enrollment records**

---

# Key Features

## Enrollment Management

The system supports complete enrollment management:

- Create enrollment
- View enrollment
- Update enrollment
- Delete enrollment
- Student and course relationship management

Creating an enrollment can automatically create or update the corresponding student and course records within a single database transaction.

---

## Server-Side Pagination

Pagination is performed on the backend.

The frontend does not download the complete dataset.

Example:

```text
5,000,000 total records
        |
        | request page 18
        v
Laravel API
        |
        | LIMIT / OFFSET
        v
PostgreSQL
        |
        | 10 records
        v
Frontend
