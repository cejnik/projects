from django.test import TestCase
from django.urls import reverse
from django.contrib.auth import get_user_model
from .models import Project, ProjectMembership, Task
from .forms import TaskCreationForm

class ProjectPermissionTests(TestCase):
    def setUp(self):
        User = get_user_model()
        self.password = 'tester123'

        self.owner = User.objects.create_user(
            username='owner',
            password=self.password,
        )
        self.member = User.objects.create_user(
            username='member',
            password=self.password,
        )
        self.outsider = User.objects.create_user(
            username='outsider',
            password=self.password,
        )

        self.project = Project.objects.create(
            name='Test Project',
            description='Test project description',
            created_by=self.owner,
        )

        ProjectMembership.objects.create(
            project = self.project,
            user = self.owner,
            role = ProjectMembership.Role.OWNER,
        )
        ProjectMembership.objects.create(
            project = self.project,
            user = self.member,
            role = ProjectMembership.Role.MEMBER,
        )

        self.task = Task.objects.create(
            project = self.project,
            title = 'Task Test',
            description = 'Description Test',
            status = Task.Status.TO_DO,
            priority = Task.Priority.MEDIUM,
            created_by = self.owner,
            assigned_to = self.member
        )


    def test_member_can_see_shared_project_on_dashboard(self):
        self.client.force_login(self.member)

        response = self.client.get(reverse('dashboard'))

        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Test Project')
    
    def test_outsider_cannot_see_shared_project_on_dashboard(self):
        self.client.force_login(self.outsider)

        response = self.client.get(reverse('dashboard'))

        self.assertEqual(response.status_code, 200)
        self.assertNotContains(response, 'Test Project')
        
    def test_member_can_open_shared_project_detail(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('project_detail_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, self.project.description)
        self.assertContains(response, self.project.name)

    def test_outsider_cannot_open_project_detail(self):
        self.client.force_login(self.outsider)
        response = self.client.get(reverse('project_detail_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 404)

    def test_owner_can_open_delete_project_page(self):
        self.client.force_login(self.owner)
        response = self.client.get(reverse('delete_project_url', args=[self.project.pk]))
        self.assertEqual(response.status_code,200)
        self.assertContains(response, self.project.name)

    def test_member_cannot_open_delete_project_page(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('delete_project_url', args=[self.project.pk]))
        self.assertEqual(response.status_code,404)

    def test_owner_can_delete_project(self):
        self.client.force_login(self.owner)
        response = self.client.post(reverse('delete_project_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 302)
        self.assertFalse(Project.objects.filter(pk=self.project.pk).exists(), self.project.pk)

    def test_outsider_cannot_delete_project(self):
        self.client.force_login(self.outsider)
        response = self.client.post(reverse('delete_project_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 404)
        self.assertTrue(Project.objects.filter(pk=self.project.pk).exists(), self.project.pk)

    def test_member_cannot_delete_project(self):
        self.client.force_login(self.member)
        response = self.client.post(reverse('delete_project_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 404)
        self.assertTrue(Project.objects.filter(pk=self.project.pk).exists(), self.project.pk)

    def test_owner_can_open_add_project_member_page(self):
        self.client.force_login(self.owner)
        response = self.client.get(reverse('add_project_member_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Add new member')

    def test_member_cannot_open_add_project_member_page(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('add_project_member_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 404)

    def test_owner_can_add_project_member(self):
        self.client.force_login(self.owner)
        data = {
            'username': self.outsider.username
        }
        response = self.client.post(reverse('add_project_member_url', args=[self.project.pk]), data)
        self.assertEqual(response.status_code,302)
        self.assertTrue(
            ProjectMembership.objects.filter(
                project=self.project,
                user=self.outsider,
                role=ProjectMembership.Role.MEMBER,
            ).exists()
            )
        
    def test_owner_cannot_add_project_member_twice(self):
        self.client.force_login(self.owner)
        data = {
            'username': self.member.username
        }
        response = self.client.post(reverse('add_project_member_url', args=[self.project.pk]), data)
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "This user is already a member of this project.")
        self.assertEqual(ProjectMembership.objects.filter(
                project=self.project,
                user=self.member,
                role=ProjectMembership.Role.MEMBER,
            ).count(),1
            )

    def test_member_can_open_create_task_page(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('task_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Create a task')

    def test_outsider_cannot_open_create_task_page(self):
        self.client.force_login(self.outsider)
        response = self.client.get(reverse('task_url', args=[self.project.pk]))
        self.assertEqual(response.status_code, 404)

    def test_member_can_create_task(self):
        self.client.force_login(self.member)
        data = {
            'title': 'New Task',
            'description': 'Task description',
            'status': Task.Status.TO_DO,
            'priority': Task.Priority.HIGH,
            'assigned_to': self.member.pk,
        }
        response = self.client.post(reverse('task_url', args=[self.project.pk]), data)
        self.assertEqual(response.status_code, 302)
        self.assertTrue(Task.objects.filter(
            project=self.project,
            title='New Task',
            description='Task description',
            status=Task.Status.TO_DO,
            priority=Task.Priority.HIGH,
            created_by=self.member,
            assigned_to=self.member
        ).exists())

    def test_outsider_cannot_create_task(self):
        self.client.force_login(self.outsider)
        data = {
            'title': 'New Task',
            'description': 'Task description',
            'status': Task.Status.TO_DO,
            'priority': Task.Priority.HIGH,
            'assigned_to': self.outsider.pk,
        }
        response = self.client.post(reverse('task_url', args=[self.project.pk]), data)
        self.assertEqual(response.status_code, 404)
        self.assertFalse(Task.objects.filter(
            project=self.project,
            title='New Task',
            description='Task description',
            status=Task.Status.TO_DO,
            priority=Task.Priority.HIGH,
            created_by=self.outsider,
            assigned_to=self.outsider
        ).exists())

    def test_member_can_open_edit_task_page(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('edit_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Update your task')

    def test_outsider_cannot_open_edit_task_page(self):
        self.client.force_login(self.outsider)
        response = self.client.get(reverse('edit_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 404)

    def test_member_can_edit_task(self):
        self.client.force_login(self.member)
        data = {
            'title': 'Updated Task',
            'description': 'Updated description',
            'status': Task.Status.IN_PROGRESS,
            'priority': Task.Priority.HIGH,
            'assigned_to': self.member.pk,
        }
        response = self.client.post(reverse('edit_task_url', args=[self.task.pk]), data)
        self.assertEqual(response.status_code, 302)
        self.task.refresh_from_db()
        self.assertEqual(self.task.title, 'Updated Task')
        self.assertEqual(self.task.description, 'Updated description')
        self.assertEqual(self.task.status, Task.Status.IN_PROGRESS)
        self.assertEqual(self.task.priority, Task.Priority.HIGH)
        self.assertEqual(self.task.assigned_to, self.member)

    def test_outsider_cannot_edit_task(self):
        self.client.force_login(self.outsider)
        data = {
            'title': 'Updated Task',
            'description': 'Updated description',
            'status': Task.Status.IN_PROGRESS,
            'priority': Task.Priority.HIGH,
            'assigned_to': self.outsider.pk,
        }
        response = self.client.post(reverse('edit_task_url', args=[self.task.pk]), data)
        self.assertEqual(response.status_code, 404)
        self.task.refresh_from_db()
        self.assertNotEqual(self.task.title, 'Updated Task')
        self.assertNotEqual(self.task.description, 'Updated description')
        self.assertNotEqual(self.task.status, Task.Status.IN_PROGRESS)
        self.assertNotEqual(self.task.priority, Task.Priority.HIGH)
        self.assertNotEqual(self.task.assigned_to, self.outsider)

    def test_member_can_open_delete_task_page(self):
        self.client.force_login(self.member)
        response = self.client.get(reverse('delete_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'This task will be permanently removed from the project.')

    def test_outsider_cannot_open_delete_task_page(self):
        self.client.force_login(self.outsider)
        response = self.client.get(reverse('delete_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 404)

    def test_member_can_delete_task(self):
        self.client.force_login(self.member)
        response = self.client.post(reverse('delete_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 302)
        self.assertFalse(Task.objects.filter(pk=self.task.pk).exists())

    def test_outsider_cannot_delete_task(self):
        self.client.force_login(self.outsider)
        response = self.client.post(reverse('delete_task_url', args=[self.task.pk]))
        self.assertEqual(response.status_code, 404)
        self.assertTrue(Task.objects.filter(pk=self.task.pk).exists())

class TaskCreationFormTests(TestCase):
    def setUp(self):
        User = get_user_model()
        self.password = 'tester123'

        self.owner = User.objects.create_user(
            username='owner',
            password=self.password,
        )
        self.member = User.objects.create_user(
            username='member',
            password=self.password,
        )

        self.outsider = User.objects.create_user(
            username='outsider',
            password=self.password,
        )

        self.project = Project.objects.create(
            name='Test Project',
            description='Test project description',
            created_by=self.owner,
        )

        ProjectMembership.objects.create(
            project = self.project,
            user = self.owner,
            role = ProjectMembership.Role.OWNER,
        )
        ProjectMembership.objects.create(
            project = self.project,
            user = self.member,
            role = ProjectMembership.Role.MEMBER,
        )
    
    def test_task_creation_form_valid_users(self):
        form_data = {
            'title': 'Test Task',
            'description': 'Task description',
            'status': Task.Status.TO_DO,
            'priority': Task.Priority.MEDIUM,
            'assigned_to': self.member.pk,
        }
        form = TaskCreationForm(data=form_data, project=self.project)
        self.assertTrue(form.is_valid())
        self.assertEqual(form.cleaned_data['assigned_to'], self.member)
    
    def test_task_creation_form_assigned_to_contains_only_project_members(self):
        form = TaskCreationForm(project=self.project)
        assigned_users = form.fields['assigned_to'].queryset
        self.assertIn(self.owner, assigned_users)
        self.assertIn(self.member, assigned_users)  
        self.assertNotIn(self.outsider, assigned_users)

class TaskFilterTests(TestCase):
    def setUp(self):
        User = get_user_model()
        self.password = 'tester123'

        self.owner = User.objects.create_user(
            username='owner',
            password=self.password,
        )

        self.project = Project.objects.create(
            name='Test Project',
            description='Test project description',
            created_by=self.owner,
        )
        ProjectMembership.objects.create(
            project = self.project,
            user = self.owner,
            role = ProjectMembership.Role.OWNER,
        )   
        self.task1 = Task.objects.create(
            project = self.project,
            title = 'Task 1',
            description = 'Description 1',
            status = Task.Status.TO_DO,
            priority = Task.Priority.MEDIUM,
            created_by = self.owner,
        )

        self.task2 = Task.objects.create(
            project = self.project,
            title = 'Task 2',
            description = 'Description 2',
            status = Task.Status.IN_PROGRESS,
            priority = Task.Priority.HIGH,
            created_by = self.owner,
        )

        self.task3 = Task.objects.create(
            project = self.project,
            title = 'Task 3',
            description = 'Description 3',
            status = Task.Status.COMPLETED,
            priority = Task.Priority.LOW,
            created_by = self.owner,
        )

    def test_project_detail_filters_tasks_by_status(self):
        self.client.force_login(self.owner)
        response = self.client.get(reverse('project_detail_url', args=[self.project.pk]), {'status': Task.Status.IN_PROGRESS})
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Task 2')
        self.assertNotContains(response, 'Task 1')
        self.assertNotContains(response, 'Task 3')

    def test_project_detail_filters_tasks_by_priority(self):
        self.client.force_login(self.owner)
        response = self.client.get(reverse('project_detail_url', args=[self.project.pk]), {'priority': Task.Priority.HIGH})
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Task 2')
        self.assertNotContains(response, 'Task 1')
        self.assertNotContains(response, 'Task 3')
    
    def test_project_detail_filters_tasks_by_status_and_priority(self):
        self.client.force_login(self.owner)
        response = self.client.get(reverse('project_detail_url', args=[self.project.pk]), {'status': Task.Status.IN_PROGRESS, 'priority': Task.Priority.HIGH})
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, 'Task 2')
        self.assertNotContains(response, 'Task 1')
        self.assertNotContains(response, 'Task 3')
