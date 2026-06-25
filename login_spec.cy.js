/// <reference types="cypress" />

describe('Authentication', () => {
  beforeEach(() => {
    // Visit the login page before each test
    cy.visit('/login.php');
  });

  it('allows an admin to log in and redirects to the dashboard', () => {
    // Check that we are on the login page
    cy.get('h1').should('contain', 'Login');

    // Use the demo admin credentials
    cy.get('input[name="email"]').type('admin@admin.com');
    cy.get('input[name="password"]').type('admin123');

    // Submit the login form
    cy.get('button[type="submit"]').click();

    // After login, the URL should be the admin dashboard
    cy.url().should('include', '/admin/index.php');
    cy.get('h1').should('contain', 'Dashboard');
  });
});