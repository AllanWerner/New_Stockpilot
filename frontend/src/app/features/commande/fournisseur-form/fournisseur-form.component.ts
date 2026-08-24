import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Fournisseur } from '../../../core/models/fournisseur.model';
import { FournisseurService } from '../fournisseur.service';

@Component({
  selector: 'sp-fournisseur-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './fournisseur-form.component.html',
  styleUrl: './fournisseur-form.component.scss',
})
export class FournisseurFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly fournisseurService = inject(FournisseurService);
  private readonly dialogRef = inject(MatDialogRef<FournisseurFormComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
    emailContact: ['', Validators.email],
    telephone: [''],
    delaiLivraisonJours: [null as number | null, Validators.min(0)],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    const raw = this.form.getRawValue();

    this.fournisseurService
      .create({
        nom: raw.nom,
        emailContact: raw.emailContact || undefined,
        telephone: raw.telephone || undefined,
        delaiLivraisonJours: raw.delaiLivraisonJours ?? undefined,
      })
      .subscribe({
        next: (fournisseur: Fournisseur) => {
          this.loading.set(false);
          this.dialogRef.close(fournisseur);
        },
        error: () => {
          this.loading.set(false);
          this.errorMessage.set('Impossible de créer ce fournisseur.');
        },
      });
  }
}
