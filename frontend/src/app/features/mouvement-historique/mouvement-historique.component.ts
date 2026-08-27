import { DatePipe } from '@angular/common';
import { Component, OnInit, effect, inject, signal } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink } from '@angular/router';
import { BoutiqueContextService } from '../../core/boutique/boutique-context.service';
import { Mouvement } from '../../core/models/mouvement.model';
import { MouvementService } from './mouvement.service';

@Component({
  selector: 'sp-mouvement-historique',
  standalone: true,
  imports: [DatePipe, RouterLink, MatTableModule, MatIconModule, MatProgressSpinnerModule, MatTooltipModule],
  templateUrl: './mouvement-historique.component.html',
  styleUrl: './mouvement-historique.component.scss',
})
export class MouvementHistoriqueComponent implements OnInit {
  private readonly mouvementService = inject(MouvementService);
  private readonly boutiqueContext = inject(BoutiqueContextService);

  readonly displayedColumns = ['dateMouvement', 'type', 'produit', 'boutique', 'quantite', 'commande'];
  readonly mouvements = signal<Mouvement[]>([]);
  readonly loading = signal(false);
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;

  // Same reliability pattern as the rest of the app: ngOnInit owns the
  // guaranteed first load, effect() reacts only to later boutique changes.
  private isFirstRun = true;

  constructor() {
    effect(() => {
      this.boutiqueContext.selectedId();

      if (this.isFirstRun) {
        this.isFirstRun = false;

        return;
      }

      this.reload();
    });
  }

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.mouvementService.list(this.boutiqueContext.selectedId() ?? undefined).subscribe({
      next: (mouvements) => {
        this.mouvements.set(mouvements);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  typeIcone(type: Mouvement['type']): string {
    return type === 'RECEPTION' ? 'move_to_inbox' : 'swap_horiz';
  }

  typeLabel(type: Mouvement['type']): string {
    return type === 'RECEPTION' ? 'Réception' : 'Transfert';
  }
}
