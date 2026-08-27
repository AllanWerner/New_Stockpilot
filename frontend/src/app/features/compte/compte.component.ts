import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'sp-compte',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './compte.component.html',
  styleUrl: './compte.component.scss',
})
export class CompteComponent {
  private readonly fb = inject(FormBuilder);
  private readonly authService = inject(AuthService);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly successMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    email: [this.authService.currentUser()?.email ?? '', [Validators.required, Validators.email]],
    nouveauMotDePasse: [''],
    motDePasseActuel: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    this.successMessage.set(null);

    const { email, nouveauMotDePasse, motDePasseActuel } = this.form.getRawValue();
    const currentEmail = this.authService.currentUser()?.email;

    this.authService
      .modifierCompte({
        motDePasseActuel,
        email: email !== currentEmail ? email : undefined,
        nouveauMotDePasse: nouveauMotDePasse || undefined,
      })
      .subscribe({
        next: () => {
          this.loading.set(false);
          this.successMessage.set('Compte mis à jour.');
          this.form.patchValue({ nouveauMotDePasse: '', motDePasseActuel: '' });
        },
        error: (err: unknown) => {
          this.loading.set(false);
          const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
          this.errorMessage.set(
            status === 401
              ? 'Mot de passe actuel incorrect.'
              : status === 409
                ? 'Cet email est déjà utilisé.'
                : 'Une erreur est survenue, réessayez.',
          );
        },
      });
  }
}
