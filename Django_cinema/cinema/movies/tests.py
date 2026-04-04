from django.test import TestCase
from django.contrib.auth.models import User
from .models import Film, Hall, Screening, Reservation, Seat
from django.urls import reverse
from django.utils import timezone

# Create your tests here.

class ReservationTests(TestCase):
    def setUp(self):
        self.password = 'tester123'

        self.user = User.objects.create_user(
            username='tester',
            password=self.password,
            email= 'test@test.com',
        )
        self.client.login(
            username = self.user.username,
            password = self.password,
        )

        self.film = Film.objects.create(
            title = 'Matrix',
            description = 'Film about future',
            duration = 150,
            release_year = 2001,
        )

        self.hall = Hall.objects.create(
            name = 'hall_test',
            capacity = 50,
        )

        self.screening = Screening.objects.create(
            film = self.film,
            hall = self.hall,
            screening_time = timezone.now(),
        )

    def test_create_reservation_with_valid_ticket_count(self):
        response = self.client.post(
            reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 2}
        )

        self.assertEqual(response.status_code, 302)
        self.assertEqual(Reservation.objects.count(),1)

        reservation = Reservation.objects.first()

        self.assertEqual(reservation.user, self.user)
        self.assertEqual(reservation.screening, self.screening)
        self.assertEqual(reservation.tickets_count,2)

    def test_ticket_invalid(self):
        response = self.client.post(
            reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 0}
        )
        self.assertEqual(response.status_code, 302)
        self.assertEqual(Reservation.objects.count(),0)
        self.assertRedirects(response, reverse('movie_detail', args=[self.screening.film.slug]))

    def test_capacity_of_halls(self):
        self.hall.capacity = 2
        self.hall.save()
        response = self.client.post(
                reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 4})
        self.assertEqual(response.status_code, 302)
        self.assertEqual(Reservation.objects.count(),0)
        self.assertRedirects(response, reverse('movie_detail', args=[self.screening.film.slug]))

    def test_update_tickets(self):
        response = self.client.post(
            reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 2})
        reservation = Reservation.objects.first()
        self.assertEqual(response.status_code, 302)
        self.assertEqual(Reservation.objects.count(),1)
        self.assertEqual(reservation.tickets_count,2)
        self.assertRedirects(response, reverse('select_seat_url', args=[reservation.id]))
        
        response = self.client.post(
            reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 4})
        reservation = Reservation.objects.first()
        self.assertEqual(response.status_code, 302)
        self.assertEqual(Reservation.objects.count(),1)
        self.assertRedirects(response, reverse('select_seat_url',args=[reservation.id]))
        self.assertEqual(reservation.tickets_count,4)


    def test_delete_reservation(self):
        reservation = Reservation.objects.create(
            user = self.user,
            screening = self.screening,
            tickets_count = 1
        )
        response = self.client.post(
            reverse('delete_reservation_url', args=[reservation.id]))
        self.assertRedirects(response, reverse('reservation'))
        self.assertEqual(Reservation.objects.count(),0)
    
    def test_anonymous_user_cannot_create_reservation(self):
        self.client.logout()
        response = self.client.post(
            reverse('create_reservation_url', args=[self.screening.id]), {'tickets_count': 1})
        self.assertEqual(response.status_code, 302)
        self.assertRedirects(response, reverse('login_url') + '?next=' + reverse('create_reservation_url', args=[self.screening.id]))
        self.assertEqual(Reservation.objects.count(),0)

        
    def test_generating_seats(self):
        hall = Hall.objects.create(
            name = 'Test Hall',
            capacity = 12,
        )
        generated_seats = Seat.objects.filter(hall=hall).count()
        self.assertEqual(generated_seats,12)
        expected_seats = [
            'Test Hall - A1', 'Test Hall - A2', 'Test Hall - A3', 'Test Hall - A4', 'Test Hall - A5', 'Test Hall - A6', 'Test Hall - A7', 'Test Hall - A8', 'Test Hall - A9', 'Test Hall - A10',
            'Test Hall - B1', 'Test Hall - B2'
        ]
        
        #actual_seats = [str(seat) for seat in Seat.objects.filter(hall=hall)]
        actual_seats = []
        for seat in Seat.objects.filter(hall=hall):
            actual_seats.append(str(seat))
        self.assertListEqual(actual_seats, expected_seats)


    def test_select_seats(self):
        reservation = Reservation.objects.create(
            user = self.user,
            screening = self.screening,
            tickets_count = 2
        )
        seat1, seat2 = Seat.objects.filter(hall=self.hall)[:2]
        seat_data = {
            'one_seat': [seat1.id, seat2.id]}
        response = self.client.post(reverse('select_seat_url', args=[reservation.id]), seat_data)
        self.assertRedirects(response, reverse('reservation'))
        reservation.refresh_from_db()
        self.assertEqual(reservation.reserved_seats.count(),2)
        reservation_seats = reservation.reserved_seats.all()
        self.assertIn(seat1, reservation_seats)
        self.assertIn(seat2, reservation_seats)

    def test_select_seats_with_invalid_count(self):
        reservation = Reservation.objects.create (
            user = self.user,
            screening = self.screening,
            tickets_count = 2
        )
        seat1 = Seat.objects.filter(hall=self.hall).first()
        seat_data = {'one_seat': seat1.id}
        response = self.client.post(reverse('select_seat_url', args=[reservation.id]), seat_data)
        self.assertRedirects(response, reverse('select_seat_url', args=[reservation.id])) 
        reservation.refresh_from_db()
        self.assertEqual(reservation.reserved_seats.count(),0)

    def test_select_already_reserved_seat(self):
        reservation1 = Reservation.objects.create(
            user = self.user,
            screening = self.screening,
            tickets_count = 1,
        )
        seat1 = Seat.objects.filter(hall=self.hall).first()
        reservation1.reserved_seats.add(seat1)

        reservation2 = Reservation.objects.create(
            user = self.user,
            screening = self.screening,
            tickets_count = 1
        )

        seat_data = {'one_seat': [seat1.id]}
        response = self.client.post(reverse('select_seat_url', args=[reservation2.id]), seat_data)
        self.assertRedirects(response,reverse('select_seat_url', args=[reservation2.id]))
        reservation2.refresh_from_db()
        self.assertEqual(reservation2.reserved_seats.count(),0)

    def test_seat_from_different_hall(self):
        reservation = Reservation.objects.create(
            user = self.user,
            screening = self.screening,
            tickets_count = 1
        )

        hall2 = Hall.objects.create(
            name = 'hall2_testing',
            capacity = 20
        )
        seat_from_different_hall = Seat.objects.filter(hall = hall2).first()
        seat_data = {'one_seat': [seat_from_different_hall.id]}
        response = self.client.post(reverse('select_seat_url', args=[reservation.id]), seat_data)
        self.assertRedirects(response, reverse('select_seat_url', args=[reservation.id]))
        reservation.refresh_from_db()
        self.assertEqual(reservation.reserved_seats.count(),0)