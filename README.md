
## ⚙️ Installation

### 1. Clone the repository
git clone https://github.com/Sandun97/0a8660d5-3e93-4634-b4d2-d1d891e5c257.git

### 2. Install dependencies
composer install

### 3. Generate the application key
php artisan key:generate

### 4. Diagnostic report's sample output
php artisan report:generate student1 diagnostic

### 5. Progress report's sample output
php artisan report:generate student1 progress

### 6. Feedback report's sample output
php artisan report:generate student1 feedback

### 7. Automated tests run
php artisan test

************************************
⚡ Continuous Integration (CI)
************************************

This project is integrated with GitHub Actions.
Every time you push changes or open a pull request, the tests will automatically run in CI.

✅ Ensures code quality
✅ Verifies that the report:generate command works correctly
✅ Prevents broken code from being merged

You can check the status of your latest builds under the Actions tab of this repository.