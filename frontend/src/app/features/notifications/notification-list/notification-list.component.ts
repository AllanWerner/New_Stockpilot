import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTooltipModule } from '@angular/material/tooltip';
import { Notification } from '../../../core/models/notification.model';
import { NotificationService } from '../../../core/notifications/notification.service';

@Component({
  selector: 'sp-notification-list',
  standalone: true,
  imports: [
    DatePipe,
    FormsModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatFormFieldModule,
    MatInputModule,
    MatTooltipModule,
  ],
  templateUrl: './notification-list.component.html',
  styleUrl: './notification-list.component.scss',
})
export class NotificationListComponent implements OnInit {
  private readonly notificationService = inject(NotificationService);

  readonly notifications = signal<Notification[]>([]);
  readonly loading = signal(true);
  readonly filterDateDebut = signal('');
  readonly filterDateFin = signal('');

  readonly notificationsAffichees = computed(() => {
    const debut = this.filterDateDebut();
    const fin = this.filterDateFin();

    return this.notifications().filter((n) => {
      const jour = n.dateCreation.slice(0, 10);

      return (!debut || jour >= debut) && (!fin || jour <= fin);
    });
  });

  ngOnInit(): void {
    this.reload();
  }

  private reload(): void {
    this.loading.set(true);
    this.notificationService.list().subscribe({
      next: (notifications) => {
        this.notifications.set(notifications);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  marquerLue(notification: Notification): void {
    if (notification.lu) {
      return;
    }

    this.notificationService.marquerLue(notification.idNotification).subscribe((updated) => {
      this.notifications.update((liste) =>
        liste.map((n) => (n.idNotification === updated.idNotification ? updated : n)),
      );
    });
  }

  basculerLue(notification: Notification, event: Event): void {
    event.stopPropagation();

    const action = notification.lu
      ? this.notificationService.marquerNonLue(notification.idNotification)
      : this.notificationService.marquerLue(notification.idNotification);

    action.subscribe((updated) => {
      this.notifications.update((liste) =>
        liste.map((n) => (n.idNotification === updated.idNotification ? updated : n)),
      );
    });
  }

  toutMarquerLu(): void {
    this.notificationService.toutMarquerLu().subscribe(() => {
      this.notifications.update((liste) => liste.map((n) => ({ ...n, lu: true })));
    });
  }

  iconePour(type: string): string {
    if (type === 'SEUIL_CRITIQUE') {
      return 'warning';
    }

    if (type === 'AJUSTEMENT_STOCK') {
      return 'tune';
    }

    return 'local_shipping';
  }
}
