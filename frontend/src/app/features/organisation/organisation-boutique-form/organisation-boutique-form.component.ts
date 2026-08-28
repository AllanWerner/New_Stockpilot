import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Boutique } from '../../../core/models/boutique.model';
import { OrganisationService } from '../organisation.service';

@Component({
  selector: 'sp-organisation-boutique-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './organisation-boutique-form.component.html',
  styleUrl: './organisation-boutique-form.component.scss',
})
export class OrganisationBoutiqueFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly organisationService = inject(OrganisationService);
  private readonly dialogRef = inject(MatDialogRef<OrganisationBoutiqueFormComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
    adresse: ['', Validators.required],
    ville: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.organisationService.createBoutique(this.form.getRawValue()).subscribe({
      next: (boutique: Boutique) => {
        this.loading.set(false);
        this.dialogRef.close(boutique);
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('Impossible de créer cette boutique.');
      },
    });
  }
}
