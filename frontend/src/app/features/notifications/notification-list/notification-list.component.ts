import { DatePipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Notification } from '../../../core/models/notification.model';
import { NotificationService } from '../../../core/notifications/notification.service';

@Component({
  selector: 'sp-notification-list',
  standalone: true,
  imports: [DatePipe, MatButtonModule, MatIconModule, MatProgressSpinnerModule],
  templateUrl: './notification-list.component.html',
  styleUrl: './notification-list.component.scss',
})
export class NotificationListComponent implements OnInit {
  private readonly notificationService = inject(NotificationService);

  readonly notifications = signal<Notification[]>([]);
  readonly loading = signal(true);

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
