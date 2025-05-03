# Rahati Platform API Documentation

## Overview

The Rahati Platform API is designed as a RESTful service accessible via HTTPS. This collection provides comprehensive access to all Rahati healthcare services including user management, appointment scheduling, consultations, accommodations, transportation, and feedback.

Base URL: `https://api.rahati.com/v1`

## Getting Started

### Prerequisites
- [Postman](https://www.postman.com/downloads/) installed on your computer
- Rahati API access credentials
- Authorization token (obtained via login endpoint)

### Importing the Collection
1. Open Postman
2. Click on "Import" in the top left corner
3. Upload the `rahati_postman_collection.json` file
4. The collection will be imported into your Postman workspace

### Setting Up Environment Variables
1. Create a new environment in Postman
2. Add the following variables:
   - `base_url`: Set to `https://api.rahati.com/v1`
   - `token`: Will be automatically set after login
   - `user_id`: ID of a user for testing user endpoints
   - `consultation_id`: ID for testing consultation endpoints

## Authentication

All protected endpoints require a valid JWT token in the Authorization header.

### Authentication Flow
1. Use the "Login" request to authenticate with valid credentials
2. The response will contain a JWT token
3. The token is automatically stored in the `{{token}}` variable
4. All subsequent protected endpoints will use this token

### Available Auth Endpoints
- **POST /auth/login**: Authenticate a user
- **POST /auth/register**: Register a new user
- **POST /auth/logout**: End a user session

## Resource Endpoints

### Users
User management endpoints for patients, providers, and administrators.

- **GET /users**: List all users (Admin only)
- **GET /users/{id}**: Get a specific user
- **POST /users**: Create a new user
- **PUT /users/{id}**: Update user details
- **DELETE /users/{id}**: Delete a user (Admin only)

### Consultations
Manage healthcare consultations between patients and providers.

- **GET /consultations**: List consultations
- **GET /consultations/{id}**: Get consultation details
- **POST /consultations**: Start a new consultation
- **PUT /consultations/{id}**: Update consultation (e.g., complete consultation)
- **DELETE /consultations/{id}**: Delete a consultation record (Admin only)

### Transportation
Coordinate transportation services for patients.

- **GET /transportation-requests**: List transportation requests
- **GET /transportation-requests/{id}**: Get details of a specific request
- **POST /transportation-requests**: Create a new transportation request
- **PUT /transportation-requests/{id}**: Update request status
- **DELETE /transportation-requests/{id}**: Cancel a transportation request

### Feedback
Collect and manage patient feedback about services.

- **GET /feedback**: List feedback entries
- **POST /feedback**: Submit new feedback
- **PUT /feedback/{id}**: Update feedback
- **DELETE /feedback/{id}**: Delete feedback entry

## Common Workflows

### Patient Appointment Booking
1. **Authentication**: Login using the POST `/auth/login` endpoint
2. **Create Appointment**: Use POST `/appointments` with patient details
3. **Book Accommodation**: Use POST `/accommodations` referencing the appointment
4. **Arrange Transportation**: Use POST `/transportation-requests` if needed
5. **Process Payment**: Use POST `/payments` to complete the booking

### Provider Consultation Management
1. **View Appointments**: GET `/appointments?providerId={id}`
2. **Start Consultation**: POST `/consultations` with appointment ID
3. **Update Consultation**: PUT `/consultations/{id}` with status and notes
4. **Complete Consultation**: Update with end time and final status

## Response Formats

All API responses use standard HTTP status codes:
- 200 OK: Successful operation
- 201 Created: Resource created
- 400 Bad Request: Invalid input
- 401 Unauthorized: Authentication required
- 403 Forbidden: Access denied
- 404 Not Found: Resource not found
- 500 Internal Server Error: Server-side error

### Example Success Response
```json
{
  "data": {
    "id": 123,
    "type": "appointment",
    "attributes": {
      "patientId": 456,
      "centerId": 10,
      "appointmentDatetime": "2025-05-01T09:00:00Z",
      "status": "Confirmed"
    }
  }
}
```

### Example Error Response
```json
{
  "error": {
    "code": "validation_error",
    "message": "The appointment date is required",
    "details": [
      {
        "field": "appointmentDatetime",
        "message": "This field is required"
      }
    ]
  }
}
```

## Security Considerations

- All API requests must use HTTPS
- Authentication uses JWT tokens with appropriate expiration
- Role-based access control enforces permissions
- Input validation prevents injection attacks
- Rate limiting protects against abuse

## Additional Resources

- [Rahati API Design Documentation](rahati_API_Design.pdf)
- [API Design Document](apid_Design.md)
- For support, contact api-support@rahati.com 