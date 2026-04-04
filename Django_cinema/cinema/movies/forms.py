from django.contrib.auth.models import User
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from django import forms

class RegistrationForm(UserCreationForm):
    username = forms.CharField(label='Enter your username: ')
    email = forms.EmailField(label='Enter your valid email: ')
    password1 = forms.CharField(widget=forms.PasswordInput, label='Password: ')
    password2 = forms.CharField(widget=forms.PasswordInput, label='Repeat password: ')

    class Meta:
        model = User
        fields = ['username', 'email', 'password1', 'password2']

class LoginForm(AuthenticationForm):
    username = forms.CharField(label='Login: ')
    password = forms.CharField(label='Password: ', widget=forms.PasswordInput)