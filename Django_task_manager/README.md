# Task Manager

This project is my junior portfolio application built with Django. The goal was not only to create a working task manager, but to understand backend logic step by step: models, views, forms, templates, permissions, database relationships, filtering, and tests.

The application allows users to create projects, add project members, manage tasks inside projects, assign tasks to project members, filter tasks by status and priority, and protect project data through membership-based permissions.

## Main Features

- User registration and login
- Project dashboard
- Project creation, editing, and deletion
- Task creation, editing, and deletion
- Project membership through `ProjectMembership`
- Owner and member roles
- Owner-only project deletion
- Owner-only member management
- Task assignment to project members
- Task status and priority choices
- Task filtering by status and priority
- Automated tests for permissions, forms, and filters

## Technologies

- Python
- Django
- PostgreSQL
- HTML
- CSS
- Django TestCase

## What I Practiced

- designing models and relationships
- working with `ForeignKey`
- using an intermediate membership model
- user authentication
- form handling and validation
- dynamic form querysets
- permission checks in views
- filtering data with GET parameters
- rendering data into templates
- writing tests for important backend rules
- separating CSS into page-specific files

## Project Structure

Main app: `tasks`

Important files:

- `task_manager/tasks/models.py` - project, task, and membership models
- `task_manager/tasks/views.py` - application flow, permissions, and filtering
- `task_manager/tasks/forms.py` - registration, project, task, and member forms
- `task_manager/tasks/urls.py` - URL routes for the tasks app
- `task_manager/tasks/templates/` - HTML templates
- `task_manager/static/` - global and page-specific CSS files
- `task_manager/tasks/tests.py` - tests for permissions, forms, and task filters

## Setup

1. Clone the repository.
2. Create and activate a virtual environment.
3. Install project dependencies.
4. Create a `.env` file with database credentials.
5. Run migrations.
6. Start the development server.

```bash
cd task_manager
python manage.py migrate
python manage.py runserver
```

## Environment Variables

Create `.env` file inside the `task_manager` folder:

```env
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASSWORD=your_database_password
DB_HOST=localhost
DB_PORT=5432
```

## Running Tests

```bash
cd task_manager
python manage.py test tasks
```

The tests currently focus mainly on project permissions, task permissions, project membership rules, task assignment form logic, and task filtering.

## Future Improvements

- task due dates
- filtering tasks by assigned user
- changing project member roles
- removing members from projects
- helper functions for repeated permission queries
- more polished UI states for empty and filtered task lists
- preparing the project for public deployment

## Author

This project was created as part of my Django and backend learning journey.
