import { Component, computed, effect, inject, signal } from '@angular/core';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { AuthService } from '../../core/auth/auth.service';
import { BoutiqueContextService } from '../../core/boutique/boutique-context.service';
import { Dashboard } from '../../core/models/dashboard.model';
import { DashboardService } from './dashboard.service';

const CHART_WIDTH = 600;
const CHART_HEIGHT = 120;

interface ChartPoint {
  x: number;
  y: number;
}

@Component({
  selector: 'sp-dashboard',
  standalone: true,
  imports: [MatCardModule, MatProgressSpinnerModule],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
})
export class DashboardComponent {
  private readonly authService = inject(AuthService);
  private readonly dashboardService = inject(DashboardService);
  private readonly boutiqueContext = inject(BoutiqueContextService);

  readonly currentUser = this.authService.currentUser;
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;
  readonly boutiques = this.boutiqueContext.boutiques;

  readonly dashboard = signal<Dashboard | null>(null);
  readonly loading = signal(true);

  readonly chartWidth = CHART_WIDTH;
  readonly chartHeight = CHART_HEIGHT;

  readonly chartDots = computed<ChartPoint[]>(() => {
    const data = this.dashboard()?.evolutionValorisation ?? [];

    if (data.length === 0) {
      return [];
    }

    const valeurs = data.map((p) => Number(p.valeur));
    const min = Math.min(...valeurs);
    const max = Math.max(...valeurs);
    const range = max - min || 1;
    const stepX = data.length > 1 ? CHART_WIDTH / (data.length - 1) : 0;

    return data.map((p, i) => ({
      x: i * stepX,
      y: CHART_HEIGHT - ((Number(p.valeur) - min) / range) * (CHART_HEIGHT - 8) - 4,
    }));
  });

  readonly chartPolyline = computed(() => this.chartDots().map((p) => `${p.x},${p.y}`).join(' '));

  constructor() {
    effect(() => {
      this.boutiqueContext.selectedId();
      this.reload();
    });
  }

  private reload(): void {
    this.loading.set(true);
    this.dashboardService.get(this.boutiqueContext.selectedId()).subscribe({
      next: (dashboard) => {
        this.dashboard.set(dashboard);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  statutClasse(statut: 'rupture' | 'critique'): string {
    return statut === 'rupture' ? 'sp-status-badge--rupture' : 'sp-status-badge--critique';
  }
}
