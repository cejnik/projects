from django.shortcuts import render, get_object_or_404, redirect
from . models import Film, Screening, Reservation, Seat
from . forms import RegistrationForm
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.db.models import Sum

# Create your views here.
def index(request):
    movies = Film.objects.all()
    return render(request, 'movies/index.html',{
        'movies':movies
    })

def movie_detail(request, slug):
    movie = get_object_or_404(Film, slug=slug)
    screenings = Screening.objects.filter(film=movie)
    return render(request, 'movies/movie_detail.html', {
        'movie':movie,
        "screenings":screenings,
                
    })

def register(request):
    if request.method == 'POST':
        form = RegistrationForm(request.POST)
        if form.is_valid():
            form.save()
            return redirect('login_url')
    else:
        form=RegistrationForm()
    return render(request, 'movies/register.html', {
        'form':form
    })

@login_required
def reservation(request):
    reservations = Reservation.objects.filter(user=request.user)
    return render(request, 'movies/reservation.html', {
        'reservations': reservations
    })

@login_required
def create_reservation(request, screening_id):
    screening_now = get_object_or_404(Screening, id = screening_id)
    try:
        tickets_count = int(request.POST.get('tickets_count'))
        if tickets_count <= 0:
            messages.error(request, "Please enter a valid ticket count.")
            return redirect('movie_detail', slug=screening_now.film.slug)
        if tickets_count > 0:
                if screening_now.hall is None:
                    messages.error(request, 'This screening does not have an assigned hall.')
                    return redirect('movie_detail', slug=screening_now.film.slug)

                existing_reservation = Reservation.objects.filter(user=request.user, screening=screening_now).first() #objekt
                current_user_old_tickets = existing_reservation.tickets_count if existing_reservation else 0 #int
                reserved_tickets_data = Reservation.objects.filter(screening=screening_now).aggregate(Sum('tickets_count')) #dictionary
                reserved_tickets = reserved_tickets_data['tickets_count__sum'] or 0 #int
                new_total_tickets = reserved_tickets - current_user_old_tickets + tickets_count #int
                if new_total_tickets > screening_now.hall.capacity:
                    messages.error(request, 'There are not enough seats available for this screening.')
                    return redirect('movie_detail', slug=screening_now.film.slug)

                reservation, created = Reservation.objects.get_or_create(
                                        user = request.user,
                                        screening = screening_now,
                                        defaults={'tickets_count': tickets_count},
                                    )
                reservation.tickets_count = tickets_count
                reservation.save()
                
    except (TypeError, ValueError):
        messages.error(request, "Please enter a valid ticket count.")
        return redirect('movie_detail', slug=screening_now.film.slug)
    messages.success(request, 'Your reservation has been saved.')
    return redirect('select_seat_url', reservation_id=reservation.id)
    
@login_required      
def delete_reservation(request, reservation_id):
    if request.method == 'POST':
        reservation = get_object_or_404(Reservation, user=request.user, id = reservation_id )
        reservation.delete()
        messages.error(request, "Your reservation has been deleted.")
        return redirect('reservation')
    else:
        return redirect('reservation')

@login_required
def select_seats(request,reservation_id):
    reservation = get_object_or_404(Reservation,id=reservation_id, user=request.user)
    screening = reservation.screening
    hall = screening.hall

    if hall is None:
        messages.error(request, 'This screening does not have an assigned hall.')
        return redirect('reservation')
    
    hall_seats = Seat.objects.filter(hall=hall)

    screening_reservations = Reservation.objects.filter(screening=screening)

    occupied_seats = set()
    for one_reservation in screening_reservations:
        if one_reservation == reservation:
            continue
        for seat in one_reservation.reserved_seats.all():
            occupied_seats.add(seat)

    available_seats = []
    for seat in hall_seats:
        if seat not in occupied_seats:
            available_seats.append(seat)

    if request.method == 'POST':
        if reservation.tickets_count != len(request.POST.getlist('one_seat')):
            messages.error(request, f'Please select exactly {reservation.tickets_count} seats.')
            return redirect('select_seat_url', reservation_id=reservation.id)
        selected_seat_ids = request.POST.getlist('one_seat')
        selected_seats = Seat.objects.filter(id__in=selected_seat_ids, hall=hall)
        if len(selected_seats) != len(selected_seat_ids):
            messages.error(request, 'Invalid seat selection.')
            return redirect('select_seat_url', reservation_id=reservation.id)
        for seat in selected_seats:
            if seat in occupied_seats:
                messages.error(request, f'Seat {seat} is already occupied. Please select different seats.')
                return redirect('select_seat_url', reservation_id=reservation.id)
        reservation.reserved_seats.set(selected_seats)
        messages.success(request, 'Your seats have been reserved successfully.')
        return redirect('reservation')

    return render(request, 'movies/select_seat.html', {
        'reservation': reservation,
        'screening': screening,
        'hall_seats': hall_seats,
        'occupied_seats': occupied_seats,
        'available_seats': available_seats,
    })
