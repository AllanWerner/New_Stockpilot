import { Component, OnInit, inject } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatToolbarModule } from '@angular/material/toolbar';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { BoutiqueContextService } from '../boutique/boutique-context.service';

@Component({
  selector: 'sp-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatToolbarModule, MatListModule, MatIconModule, MatMenuModule],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly boutiqueContext = inject(BoutiqueContextService);

  readonly currentUser = this.authService.currentUser;
  readonly boutiques = this.boutiqueContext.boutiques;
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;

  ngOnInit(): void {
    this.authService.chargerProfil().subscribe();
    this.boutiqueContext.charger().subscribe();
  }

  logout(): void {
    this.authService.logout();
  }

  selectionnerBoutique(idBoutique: number | null): void {
    this.boutiqueContext.selectionner(idBoutique);
  }
}
