import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {HomeScreen} from '@/screens/peladas/HomeScreen';
import {CreatePeladaScreen} from '@/screens/peladas/CreatePeladaScreen';
import {JoinPeladaScreen} from '@/screens/peladas/JoinPeladaScreen';
import {PeladaDetailScreen} from '@/screens/peladas/PeladaDetailScreen';
import {PresenceScreen} from '@/screens/peladas/PresenceScreen';
import {DrawScreen} from '@/screens/draw/DrawScreen';
import {VotingScreen} from '@/screens/voting/VotingScreen';
import {RankingScreen} from '@/screens/ranking/RankingScreen';
import {ProfileScreen} from '@/screens/profile/ProfileScreen';
import {theme} from '@/theme';
import {t} from '@/i18n/pt-BR';
import type {AppStackParamList} from './types';

const Stack = createNativeStackNavigator<AppStackParamList>();

export function AppStack(): React.JSX.Element {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {backgroundColor: theme.colors.primary},
        headerTintColor: '#fff',
        headerTitleStyle: {fontWeight: '700'},
      }}>
      <Stack.Screen name="Home" component={HomeScreen} options={{title: t.peladas.home}} />
      <Stack.Screen
        name="CreatePelada"
        component={CreatePeladaScreen}
        options={{title: t.peladas.create}}
      />
      <Stack.Screen
        name="JoinPelada"
        component={JoinPeladaScreen}
        options={{title: t.peladas.join}}
      />
      <Stack.Screen
        name="PeladaDetail"
        component={PeladaDetailScreen}
        options={{title: 'Pelada'}}
      />
      <Stack.Screen
        name="Presence"
        component={PresenceScreen}
        options={{title: t.presence.title}}
      />
      <Stack.Screen name="Draw" component={DrawScreen} options={{title: 'Cadê Os Times?'}} />
      <Stack.Screen name="Voting" component={VotingScreen} options={{title: t.voting.title}} />
      <Stack.Screen name="Ranking" component={RankingScreen} options={{title: t.ranking.title}} />
      <Stack.Screen name="Profile" component={ProfileScreen} options={{title: t.profile.title}} />
    </Stack.Navigator>
  );
}
