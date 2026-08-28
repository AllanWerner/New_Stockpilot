import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Categorie } from '../../../core/models/produit.model';
import { CategorieService } from '../categorie.service';

@Component({
  selector: 'sp-categorie-form',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './categorie-form.component.html',
  styleUrl: './categorie-form.component.scss',
})
export class CategorieFormComponent {
  private readonly fb = inject(FormBuilder);
  private readonly categorieService = inject(CategorieService);
  private readonly dialogRef = inject(MatDialogRef<CategorieFormComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    nom: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.categorieService.create(this.form.getRawValue()).subscribe({
      next: (categorie: Categorie) => {
        this.loading.set(false);
        this.dialogRef.close(categorie);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
        this.errorMessage.set(
          status === 409 ? 'Une catégorie avec ce nom existe déjà.' : 'Impossible de créer cette catégorie.',
        );
      },
    });
  }
}
