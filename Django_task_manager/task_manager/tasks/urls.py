from django.urls import path
from . import views
from django.contrib.auth import views as auth_views
from .forms import LoginForm

urlpatterns = [
    path('',auth_views.LoginView.as_view(template_name='tasks/index.html', authentication_form = LoginForm), name='login_url'),
    path('logout/', auth_views.LogoutView.as_view(), name='logout_url'),
    path('register/', views.register, name='registration_url'),
    path('dashboard/', views.dashboard, name='dashboard'),
    path('create-project/', views.create_project, name='create_project_url'),
    path('project-detail/<int:pk>/', views.project_detail, name='project_detail_url'),
    path('create-task/<int:project_id>/', views.create_task, name='task_url'),
    path('projects/<int:pk>/delete/', views.delete_project, name='delete_project_url'),
    path('tasks/<int:pk>/delete/', views.delete_task, name= 'delete_task_url'),
    path('projects/<int:pk>/edit/', views.edit_project, name='project_edit_url'),
    path('tasks/<int:pk>/edit/', views.edit_task, name='edit_task_url'),
    path('projects/<int:pk>/members/add/', views.add_project_member, name='add_project_member_url'),

]
