<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    // Properti ini akan menampung data dari form
    public ?array $data = [];

    // Judul halaman yang akan tampil di browser dan di atas halaman
    protected static ?string $title = 'Profil Saya';

    // Ikon untuk menu navigasi
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    // Nama yang tampil di menu navigasi sidebar
    protected static ?string $navigationLabel = 'Profil Saya';

    // Lokasi file view Blade yang akan digunakan
    protected static string $view = 'filament.sales.pages.edit-profile';
    protected static bool $shouldRegisterNavigation = false;

    /**
     * Method mount() berjalan saat halaman pertama kali dibuka.
     * Tugasnya adalah mengisi form dengan data user yang sedang login.
     */
    public function mount(): void
    {
        // Ambil data user yang sedang login dan isi ke properti $data
        $this->form->fill(auth()->user()->attributesToArray());
    }

    /**
     * Method form() digunakan untuk mendefinisikan semua field input
     * yang akan ada di halaman profile.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Profil')
                    ->description('Perbarui informasi profil dan alamat email Anda.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required(),
                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('alamat')
                    ]),
                
                Section::make('Ubah Password')
                    ->description('Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password() 
                            ->confirmed()
                            ->helperText('Kosongkan jika tidak ingin mengubah password.')
                            ->formatStateUsing(fn () => null)
                            ->dehydrated(fn ($state) => filled($state)), 
                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->dehydrated(false), // Jangan simpan field ini ke database
                    ]),
            ])
            ->statePath('data'); // Hubungkan form ini dengan properti $data
    }

    /**
     * Method ini akan dipanggil saat tombol "Save" ditekan.
     */
    public function save(): void
    {
        // Ambil data yang sudah divalidasi dari form
        $data = $this->form->getState();

        // Ambil record user yang sedang login
        $user = auth()->user();
        
        // Pisahkan data password agar tidak ter-update jika kosong
        $passwordData = [];
        if (!empty($data['password'])) {
            $passwordData['password'] = Hash::make($data['password']);
        }

        // Update data user dengan data baru
        $user->update(array_merge(
            ['name' => $data['name'], 'email' => $data['email'], 'alamat' => $data['alamat']],
            $passwordData
        ));

        // Kirim notifikasi sukses
        Notification::make()
            ->title('Profil berhasil disimpan')
            ->success()
            ->send();
            
        // Refresh halaman untuk menampilkan data terbaru (termasuk di header)
        $this->redirect(static::getUrl(), navigate: true);
    }
}