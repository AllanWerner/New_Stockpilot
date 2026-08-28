import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Produit } from '../../../core/models/produit.model';
import { CatalogService } from '../catalog.service';

export interface CatalogPrixDialogData {
  idProduit: number;
  nom: string;
  prixActuel: string;
}

@Component({
  selector: 'sp-catalog-prix-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-prix-form.component.html',
  styleUrl: './catalog-prix-form.component.scss',
})
export class CatalogPrixFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly catalogService = inject(CatalogService);
  private readonly dialogRef = inject(MatDialogRef<CatalogPrixFormComponent>);
  readonly data = inject<CatalogPrixDialogData>(MAT_DIALOG_DATA);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    prixAchat: [this.data.prixActuel, [Validators.required, Validators.pattern(/^\d+(\.\d{1,2})?$/)]],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.catalogService.modifierPrix(this.data.idProduit, this.form.getRawValue()).subscribe({
      next: (produit: Produit) => {
        this.loading.set(false);
        this.dialogRef.close(produit);
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('Impossible de modifier le prix.');
      },
    });
  }
}
