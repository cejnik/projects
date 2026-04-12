from django.db import models
from django.conf import settings

class Project(models.Model):
    name = models.CharField(max_length=50)
    description = models.TextField(max_length=400, blank=True)
    created_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f'{self.name} created by {self.created_by}'
    
class Task(models.Model):
    project = models.ForeignKey(Project, on_delete=models.CASCADE)
    title = models.CharField(max_length=50)
    description = models.TextField(blank=True)
    class Status(models.TextChoices):
        TO_DO = 'to_do', 'To Do'
        IN_PROGRESS = 'in_progress', 'In Progress'
        COMPLETED = 'completed', 'Completed'
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.TO_DO)
    class Priority(models.TextChoices):
        LOW = 'low', 'Low'
        MEDIUM = 'medium', 'Medium'
        HIGH = 'high', 'High'
    priority = models.CharField(max_length=10, choices=Priority.choices, default=Priority.LOW)
    created_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='created_tasks')
    assigned_to = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, blank=True, null=True, related_name='assigned_tasks')
    created_at = models.DateTimeField(auto_now_add=True)

    
    def __str__(self):
        return f'{self.title} {self.project}'

class ProjectMembership(models.Model):
    project = models.ForeignKey(Project, on_delete=models.CASCADE)
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)
    class Role(models.TextChoices):
        OWNER = 'owner', 'Owner'
        MEMBER = 'member', 'Member'
    role = models.CharField(max_length=10, choices=Role.choices, default=Role.MEMBER)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['project', 'user'], name='unique_user_and_project')
        ]
        ordering = [
            'project', 'user'
        ]


    def __str__(self):
        return f'{self.user} - {self.project} - {self.role}'

