# Rahati Platform API Documentation

## Overview

This Postman collection provides comprehensive access to the Rahati healthcare platform API, including user management, appointment scheduling, center management, and more. The API follows RESTful principles and uses JWT token-based authentication.

## Getting Started

### Prerequisites
- [Postman](https://www.postman.com/downloads/) installed on your computer
- Rahati API access credentials

### Importing the Collection
1. Open Postman
2. Click on "Import" in the top left corner
3. Upload the `rahati_postman_collection.json` file
4. The collection will be imported into your Postman workspace

## Authentication

The API uses Bearer token authentication. To authenticate:

1. Use the "Login" endpoint in the Authentication folder
2. Provide your email and password
3. The response will include a token
4. This token will be automatically set as the `auth_token` variable for subsequent requests

## Role-Based Access Control

The API implements role-based access control with the following roles:

- **Patient**: Can access their own data only
- **Provider**: Can access their assigned appointments and related data
- **Admin**: Can access and manage data for their assigned center only
- **Superuser**: Can access and manage all data across all centers

## Key Features

### User Management
- Create, read, update, and delete users
- Role-based permissions
- Center assignment for Admin users

### Center Management
- Create, read, update, and delete healthcare centers
- Admin users can only manage their assigned center
- Superusers can manage all centers

### Appointments
- Schedule appointments at specific centers
- Assign providers to appointments
- Filter appointments by center, date range, and status
- Admin users can only view appointments for their assigned center

## Error Handling

The API returns standard HTTP status codes:

- 200 OK: Successful operation
- 201 Created: Resource successfully created
- 400 Bad Request: Invalid data
- 401 Unauthorized: Authentication required
- 403 Forbidden: Access denied
- 404 Not Found: Resource does not exist
- 409 Conflict: Data conflict
- 500 Internal Server Error: Server error

Error responses include a message explaining the error.

## Security Considerations

- All API requests must use HTTPS
- Authentication uses JWT tokens with appropriate expiration
- Role-based access control enforces permissions
- Input validation prevents injection attacks

## Additional Resources

For more detailed information about the API, refer to the API Design Document (`apid_Design.md`).

For support, contact api-support@rahati.com
