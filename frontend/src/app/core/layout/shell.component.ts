import { Component, OnInit, inject } from '@angular/core';
import { MatBadgeModule } from '@angular/material/badge';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatToolbarModule } from '@angular/material/toolbar';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { BoutiqueContextService } from '../boutique/boutique-context.service';
import { NotificationService } from '../notifications/notification.service';

@Component({
  selector: 'sp-shell',
  standalone: true,
  imports: [
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    MatToolbarModule,
    MatListModule,
    MatIconModule,
    MatMenuModule,
    MatBadgeModule,
  ],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly boutiqueContext = inject(BoutiqueContextService);
  private readonly notificationService = inject(NotificationService);

  readonly currentUser = this.authService.currentUser;
  readonly isVendeurSeul = this.authService.isVendeurSeul;
  readonly boutiques = this.boutiqueContext.boutiques;
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;
  readonly compteNotificationsNonLues = this.notificationService.compteNonLues;

  ngOnInit(): void {
    this.authService.chargerProfil().subscribe();
    this.boutiqueContext.charger().subscribe();
    this.notificationService.rafraichirCompte();
  }

  logout(): void {
    this.authService.logout();
  }

  selectionnerBoutique(idBoutique: number | null): void {
    this.boutiqueContext.selectionner(idBoutique);
  }
}
