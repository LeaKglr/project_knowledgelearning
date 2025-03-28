# KnowledgeLearning

KnowledgeLearning is a platform that allows users to purchase and take online courses.
It allows:
- Purchasing courses and individual lessons via Stripe
- Managing users and roles
- Validating lessons and earning certifications

## Installation

### Prerequisites
- PHP >=8.2
- Composer ([installation guide](https://getcomposer.org/download/))
- Symfony CLI ([installation guide](https://symfony.com/download))
- Node.js et npm 
- MySQL

### Installation Steps 

#### Clone the repository
git clone https://github.com/LeaKglr/project_knowledgelearning
cd project_knowledgelearning

#### Install PHP dependencies
composer install
npm install

#### Create and configure the environment file
cp .env

#### Create the database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

## Development server 
Run `symfony server:start` for a dev server. Access the site at `http://127.0.0.1:8000`.

## Configuration
- Stripe: add API Key in the .env
- Email: configure the sender in .env
- Security: role Management with Symfony Security

## Use of the site
Access the admin panel at: /admin
Access registration and login: /register and /login
Access for the course: /theme/{name}

## Running Tests
php bin/phpunit

## Documentation 
To access the documentation, navigate to : `http://127.0.0.1:8000/docs/namespaces/app.html`
To view the PDF version, run : `start documentation.pdf`.

## Author 
👩‍💻 LeaKglr
📧 Contact: leakugler.dev@gmail.com
🔗 LinkedIn: `www.linkedin.com/in/lea-kugler`