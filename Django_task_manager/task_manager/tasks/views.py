from django.shortcuts import render, redirect, get_object_or_404
from .forms import RegistrationForm, ProjectCreationForm, TaskCreationForm, AddProjectMemberForm
from django.contrib.auth.decorators import login_required
from .models import Project, Task, ProjectMembership

def register(request):
    if request.method == 'POST':
        form = RegistrationForm(request.POST)
        if form.is_valid():
            form.save()
            return redirect('login_url')
    else:
        form = RegistrationForm()
    return render(request, 'tasks/register.html', {
        'form': form
    })

@login_required
def dashboard(request):
    projects= Project.objects.filter(projectmembership__user = request.user).prefetch_related('projectmembership_set__user').distinct()
    return render(request, 'tasks/dashboard.html', {
        'projects':projects
    })

@login_required
def create_project(request):
    if request.method == 'POST':
        form = ProjectCreationForm(request.POST)
        if form.is_valid():
            project = Project(
                name = form.cleaned_data['name'],
                description = form.cleaned_data['description'],
                created_by = request.user,
            )
            project.save()
            ProjectMembership.objects.create(
                project = project,
                user = request.user,
                role = ProjectMembership.Role.OWNER,
            )
            return redirect('dashboard')
    else:
        form = ProjectCreationForm()
    return render(request, 'tasks/project_creation.html', {
        'form': form
    })

@login_required
def project_detail(request, pk):
    project = get_object_or_404(Project, pk = pk, projectmembership__user = request.user)
    membership = ProjectMembership.objects.get(project=project, user = request.user)
    memberships = ProjectMembership.objects.filter(project=project).select_related('user')
    is_owner = membership.role == ProjectMembership.Role.OWNER
    tasks = Task.objects.filter(project=project)
    status = request.GET.get('status')
    priority = request.GET.get("priority")
    if status:
        tasks = tasks.filter(status = status)
    if priority:
        tasks = tasks.filter(priority = priority)

    return render(request, 'tasks/project_detail.html', {
        'project': project,
        'tasks': tasks,
        'is_owner': is_owner,
        'memberships': memberships,
        'selected_status':status,
        'selected_priority': priority,
        'status_choices': Task.Status.choices,
        'priority_choices': Task.Priority.choices,

    })

@login_required
def create_task(request, project_id):
    project= get_object_or_404(Project, pk = project_id, projectmembership__user = request.user)
    if request.method == 'POST':
        form = TaskCreationForm(request.POST, project=project)
        if form.is_valid():
            task = Task(
                project = project,
                title = form.cleaned_data['title'],
                description = form.cleaned_data['description'],
                status = form.cleaned_data['status'],
                priority = form.cleaned_data['priority'],
                created_by = request.user,
                assigned_to = form.cleaned_data['assigned_to']              
            )
            task.save()
            return redirect('project_detail_url', project.pk )

    else:
        form = TaskCreationForm(project=project)
    return render(request, 'tasks/task_creation.html', {
        'form': form
    })

@login_required
def delete_project(request, pk):
    project = get_object_or_404(Project, pk = pk, projectmembership__user = request.user, projectmembership__role = ProjectMembership.Role.OWNER)
    if request.method == 'POST':
        project.delete()
        return redirect('dashboard')
    else:
        return render(request, 'tasks/delete_project.html', {
            'project': project,
        })
        
@login_required
def delete_task(request, pk):
    task = get_object_or_404(Task, pk = pk, project__projectmembership__user = request.user)
    project= task.project
    if request.method == 'POST':
        task.delete()
        return redirect('project_detail_url', project.pk)
    else:
        return render(request, 'tasks/delete_task.html', {
            'task': task
        })

@login_required
def edit_project(request, pk):
    project = get_object_or_404(Project, pk = pk, projectmembership__user = request.user)
    if request.method == 'POST':
        form = ProjectCreationForm(request.POST, instance = project)
        if form.is_valid():
            form.save()
            return redirect('project_detail_url', project.pk)
    else:
        form = ProjectCreationForm(instance=project)
    return render(request, 'tasks/edit_project.html', {
        'project': project,
        'form': form
    })

@login_required
def edit_task(request, pk):
    task = get_object_or_404(Task, pk = pk, project__projectmembership__user = request.user)
    if request.method == 'POST':
        form = TaskCreationForm(request.POST, instance = task, project = task.project)
        if form.is_valid():
            form.save()
            return redirect('project_detail_url', task.project.pk)
    else:
        form = TaskCreationForm(instance = task, project = task.project)
    return render(request, 'tasks/edit_task.html', {
        'form': form,
        'task': task
    })

@login_required
def add_project_member(request, pk):
    project = get_object_or_404(Project, pk = pk, projectmembership__user = request.user, projectmembership__role = ProjectMembership.Role.OWNER)   
    if request.method == 'POST':
        form = AddProjectMemberForm(request.POST)
        if form.is_valid():
            user_to_add = form.user
            if ProjectMembership.objects.filter(project = project, user = user_to_add).exists():
                form.add_error("username", "This user is already a member of this project.")
            else:
                projectmembership = ProjectMembership(
                    project = project,
                    user = user_to_add,
                    role = ProjectMembership.Role.MEMBER,
                )
                projectmembership.save()
                return redirect('project_detail_url', project.pk)
    else:
        form = AddProjectMemberForm()
    return render(request, 'tasks/add_project_member.html', {
        'form': form,
        'project':project,

    })
