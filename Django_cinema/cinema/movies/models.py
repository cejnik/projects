from django.db import models
from django.utils.text import slugify
from django.conf import settings
import math


# Create your models here.
class Film(models.Model):
    title = models.CharField(max_length=70)
    description = models.TextField()
    duration = models.PositiveSmallIntegerField()
    release_year = models.PositiveSmallIntegerField(null=True, blank=True)
    created_at = models.DateField(auto_now_add=True)
    poster = models.ImageField(upload_to='posters/', null=True, blank=True)
    slug = models.SlugField(unique=True, blank=True)

    def __str__(self):
        return self.title
    class Meta:
        ordering = ['-created_at']
    
    #Logic to Slugify
    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = slugify(self.title)
        super().save(*args, **kwargs)

class Hall(models.Model):
    name = models.CharField(max_length=50)
    capacity = models.PositiveSmallIntegerField()
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f'{self.name}: {self.capacity}'
    
    def create_seats(self):
        row_letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G']

        for seat_index in range(1, self.capacity + 1):
            row_index = math.floor((seat_index-1) / 10)
            row = row_letters[row_index]
            seat_number = seat_index - row_index*10
        
            Seat.objects.create(
                hall=self,
                row = row,
                number = seat_number,
            )
    
    def save(self, *args, **kwargs):
        is_new = self.pk is None
        super().save(*args, **kwargs)
        if is_new:
            self.create_seats()

class Seat(models.Model):
    hall = models.ForeignKey(Hall, on_delete=models.CASCADE)
    number = models.PositiveSmallIntegerField()
    row = models.CharField(max_length=2)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['hall', 'row', 'number'],
                                    name='unique_seat_in_hall')
        ]
        ordering = [
            'hall', 'row', 'number'
        ]
   
    def __str__(self):
        return f'{self.hall.name} - {self.row}{self.number}'

class Screening(models.Model):
    film = models.ForeignKey(Film , on_delete=models.CASCADE )
    hall = models.ForeignKey(Hall,on_delete=models.CASCADE,null=True, blank=True )
    screening_time = models.DateTimeField()
    created_at = models.DateTimeField(auto_now_add=True)   

    def __str__(self):
        return f"{self.film} - {self.screening_time} place: {self.hall} "
    
class Reservation(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, null=True, blank=True)
    screening = models.ForeignKey(Screening, on_delete=models.CASCADE)
    tickets_count = models.PositiveSmallIntegerField()
    reserved_seats = models.ManyToManyField(Seat)
    created_at = models.DateTimeField(auto_now_add=True)


