# Cinema Reservation System

This project is my junior portfolio application built with Django. The goal was not only to "make something work", but to understand how a web application is built layer by layer: models, views, templates, validation, tests, and admin.

The application allows users to browse movies, open movie details, choose a screening, reserve tickets, select exact seats, and then view the reservation in the `My reservations` page.

## Main Features

- User registration and login
- Movie overview with detail page
- Screening selection
- Reservation by ticket count
- Exact seat selection
- Reservation overview in `My reservations`
- Reservation deletion
- Basic Django admin support
- Automated tests for key flows

## Technologies

- Python
- Django
- PostgreSQL
- HTML
- CSS
- Django TestCase

## What I Practiced

- designing models and relationships
- working with `ForeignKey` and `ManyToManyField`
- user authentication
- form handling and validation
- rendering data into templates
- connecting seat selection with reservations
- writing tests for important scenarios
- basic work with Django admin

## Project Structure

Main app: `movies`

Important files:

- `cinema/movies/models.py` - models and domain logic
- `cinema/movies/views.py` - application flow and validation
- `cinema/movies/templates/` - HTML templates
- `cinema/movies/static/` - CSS files
- `cinema/movies/tests.py` - tests for reservation and seat selection flow
- `cinema/movies/admin.py` - admin configuration

## Setup

1. Clone the repository.
2. Create and activate a virtual environment.
3. Install dependencies.
4. Create a `.env` file with database credentials.
5. Run migrations.
6. Start the development server.

```bash
pip install -r requirements.txt
python manage.py migrate
python manage.py runserver
```

## Environment Variables

Create `.env` file:

```env
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASSWORD=your_database_password
DB_HOST=localhost
DB_PORT=5432
```

## Running Tests

```bash
python manage.py test
```

The tests currently focus mainly on reservations, ticket-count validation, and the seat-selection flow.

## Future Improvements

- movie recommendations based on user clicks
- improved admin views
- more tests and edge cases
- preparing the project for public deployment

## Author

This project was created as part of my Django and backend learning journey.
