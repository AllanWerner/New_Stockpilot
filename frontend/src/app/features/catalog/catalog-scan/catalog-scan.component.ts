import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Produit } from '../../../core/models/produit.model';
import { CatalogService } from '../catalog.service';

@Component({
  selector: 'sp-catalog-scan',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-scan.component.html',
  styleUrl: './catalog-scan.component.scss',
})
export class CatalogScanComponent {
  private readonly fb = inject(FormBuilder);
  private readonly catalogService = inject(CatalogService);
  private readonly dialogRef = inject(MatDialogRef<CatalogScanComponent>);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    codeBarre: ['', Validators.required],
  });

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.catalogService.scan({ codeBarre: this.form.getRawValue().codeBarre }).subscribe({
      next: (produit: Produit) => {
        this.loading.set(false);
        this.dialogRef.close(produit);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
        this.errorMessage.set(
          status === 409 ? 'Un produit avec ce code-barres existe déjà.' : 'Impossible de scanner ce produit.',
        );
      },
    });
  }
}
