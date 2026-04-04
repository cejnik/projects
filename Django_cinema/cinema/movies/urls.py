from django.urls import path
from . import views
from django.contrib.auth import views as auth_views
from . forms import LoginForm

urlpatterns = [
    path('', views.index, name='homepage'),
    path('movies/<slug:slug>/', views.movie_detail, name='movie_detail'),
    path('registration/', views.register, name='registration_url'),
    path("login/", auth_views.LoginView.as_view(template_name='movies/login.html', authentication_form=LoginForm), name='login_url'),
    path('reservation/', views.reservation, name='reservation'),
    path('create-reservation/<int:screening_id>/', views.create_reservation, name='create_reservation_url'),
    path("logout/",auth_views.LogoutView.as_view(), name='logout_url'),
    path('delete-reservation/<int:reservation_id>/', views.delete_reservation, name='delete_reservation_url'),
    path('select-seat/<int:reservation_id>/', views.select_seats, name='select_seat_url')
]
