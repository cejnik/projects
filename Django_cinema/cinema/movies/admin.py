from django.contrib import admin
from . models import Film, Screening, Hall, Seat, Reservation

# Register your models here.
admin.site.register(Film)
admin.site.register(Screening)
admin.site.register(Hall)

@admin.register(Seat)
class SeatAdmin(admin.ModelAdmin):
    list_display = ['hall', 'row', 'number']

@admin.register(Reservation)
class ReservationAdmin(admin.ModelAdmin):
    @admin.display(description='Movie')
    def film(self, obj):
        return obj.screening.film
    def hall(self, obj):
        return obj.screening.hall
    def screening_time(self, obj):
        return obj.screening.screening_time


    list_display = ['user', 'tickets_count', 'film', 'hall', 'screening_time']


