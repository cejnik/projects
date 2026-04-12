from django.contrib.auth.models import User
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from django import forms
from .models import Project, Task, ProjectMembership
from django.contrib.auth import get_user_model

class RegistrationForm(UserCreationForm):
    username = forms.CharField(label='Insert your username')
    email = forms.EmailField(label='Enter your email')
    password1 = forms.CharField(label='Enter your password', widget=forms.PasswordInput)
    password2 = forms.CharField(label='Re-type your password', widget=forms.PasswordInput)

    class Meta:
        model = User
        fields = ['username', 'email', 'password1', 'password2']

class LoginForm(AuthenticationForm):
    username = forms.CharField(label='Insert your username')
    password = forms.CharField(label='Insert your password', widget=forms.PasswordInput)


class ProjectCreationForm(forms.ModelForm):
  
    class Meta:
        model = Project
        fields = ['name', 'description']
    

class TaskCreationForm(forms.ModelForm):

    def __init__(self, *args, **kwargs):
        project = kwargs.pop('project', None)
        super().__init__(*args, **kwargs)

        if project:
            User = get_user_model()
            self.fields["assigned_to"].queryset = User.objects.filter(projectmembership__project=project)

    class Meta:
        model = Task
        fields = ['title', 'description', 'status', 'priority', 'assigned_to']


class AddProjectMemberForm(forms.Form):
    username = forms.CharField(label='Add new member')
    def clean_username(self):
        username = self.cleaned_data["username"]
        User = get_user_model()

        try:
            user = User.objects.get(username = username)
        except User.DoesNotExist:
            raise forms.ValidationError('User with this username does not exist.')
        self.user = user
        return username



