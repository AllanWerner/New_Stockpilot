import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { Boutique } from '../../../core/models/boutique.model';
import { Employe } from '../../../core/models/employe.model';
import { OrganisationService } from '../organisation.service';

export interface OrganisationAffecterDialogData {
  boutique: Boutique;
  employes: Employe[];
}

@Component({
  selector: 'sp-organisation-affecter-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatSelectModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './organisation-affecter-form.component.html',
  styleUrl: './organisation-affecter-form.component.scss',
})
export class OrganisationAffecterFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly organisationService = inject(OrganisationService);
  private readonly dialogRef = inject(MatDialogRef<OrganisationAffecterFormComponent>);
  private readonly data = inject<OrganisationAffecterDialogData>(MAT_DIALOG_DATA);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly boutique = this.data.boutique;
  readonly employes = this.data.employes;

  readonly form = this.fb.nonNullable.group({
    idEmploye: [0, [Validators.required, Validators.min(1)]],
    posteEmploye: ['VENDEUR' as 'RESPONSABLE' | 'VENDEUR', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.organisationService.affecterEmploye(this.boutique.idBoutique, this.form.getRawValue()).subscribe({
      next: () => {
        this.loading.set(false);
        this.dialogRef.close(true);
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set("Impossible d'affecter cet employé.");
      },
    });
  }
}
