import { Component, OnInit, inject } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';
import { MatToolbarModule } from '@angular/material/toolbar';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../auth/auth.service';
import { MeResponse } from '../models/auth.model';

@Component({
  selector: 'sp-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatToolbarModule, MatListModule, MatIconModule, MatMenuModule],
  templateUrl: './shell.component.html',
  styleUrl: './shell.component.scss',
})
export class ShellComponent implements OnInit {
  private readonly authService = inject(AuthService);

  readonly currentUser = this.authService.currentUser;

  ngOnInit(): void {
    this.authService.chargerProfil().subscribe();
  }

  logout(): void {
    this.authService.logout();
  }

  boutiqueLabel(user: MeResponse): string {
    if (user.boutiques.length > 0) {
      return user.boutiques[0].nomBoutique;
    }

    return user.role === 'GERANT' ? 'Toutes boutiques' : '';
  }
}
