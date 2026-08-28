import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { Employe } from '../../../core/models/employe.model';
import { OrganisationService } from '../organisation.service';

@Component({
  selector: 'sp-organisation-employe-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './organisation-employe-form.component.html',
  styleUrl: './organisation-employe-form.component.scss',
})
export class OrganisationEmployeFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly organisationService = inject(OrganisationService);
  private readonly dialogRef = inject(MatDialogRef<OrganisationEmployeFormComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
    prenom: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    motDePasse: ['', [Validators.required, Validators.minLength(8)]],
    role: ['EMPLOYE' as 'GERANT' | 'EMPLOYE', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.organisationService.createEmploye(this.form.getRawValue()).subscribe({
      next: (employe: Employe) => {
        this.loading.set(false);
        this.dialogRef.close(employe);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
        this.errorMessage.set(status === 409 ? 'Un compte avec cet email existe déjà.' : 'Impossible de créer ce compte.');
      },
    });
  }
}
