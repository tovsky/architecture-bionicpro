import React from 'react';
import { ReactKeycloakProvider } from '@react-keycloak/web';
import Keycloak, { KeycloakConfig, KeycloakInitOptions } from 'keycloak-js';
import ReportPage from './components/ReportPage';

const keycloakConfig: KeycloakConfig = {
    url: 'http://localhost:8080', // Жёстко прописываем для теста
    realm: 'reports-realm',
    clientId: 'reports-frontend'
};

const initOptions: KeycloakInitOptions = {
    pkceMethod: 'S256',
    flow: 'standard',
    checkLoginIframe: false,
    onLoad: 'login-required',
    silentCheckSsoRedirectUri: window.location.origin + '/silent-check-sso.html'
};

const keycloak = new Keycloak(keycloakConfig);

const App: React.FC = () => {
    return (
        <ReactKeycloakProvider
            authClient={keycloak}
            initOptions={initOptions}
            autoRefreshToken={true}
            onTokens={({ token }) => {
                localStorage.setItem('kc_token', token || '');
            }}
        >
            <div className="App">
                <ReportPage />
            </div>
        </ReactKeycloakProvider>
    );
};

export default App;