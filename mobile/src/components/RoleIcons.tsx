import { StyleSheet, View } from 'react-native';
import type { Role } from '../types';

export function RoleIcon({ role }: { role: Role }) {
  if (role === 'teacher') return <TeacherIcon />;
  if (role === 'parent') return <ParentIcon />;
  return <StudentIcon />;
}

function StudentIcon() {
  return (
    <View style={styles.stage}>
      <View style={styles.capTop} />
      <View style={styles.capBoard} />
      <View style={styles.tassel} />
      <View style={styles.head} />
      <View style={styles.body} />
    </View>
  );
}

function TeacherIcon() {
  return (
    <View style={styles.stage}>
      <View style={styles.board}>
        <View style={styles.chalk} />
        <View style={[styles.chalk, styles.chalkShort]} />
      </View>
      <View style={styles.apple}>
        <View style={styles.leaf} />
      </View>
    </View>
  );
}

function ParentIcon() {
  return (
    <View style={[styles.stage, styles.family]}>
      <View style={styles.adult}>
        <View style={styles.adultHead} />
        <View style={styles.adultBody} />
      </View>
      <View style={styles.child}>
        <View style={styles.childHead} />
        <View style={styles.childBody} />
      </View>
    </View>
  );
}

const yellow = '#FFFFFF';

const styles = StyleSheet.create({
  stage: { width: 42, height: 42, alignItems: 'center', justifyContent: 'flex-end' },
  capTop: { position: 'absolute', top: 0, width: 22, height: 6, borderRadius: 2, backgroundColor: yellow },
  capBoard: { position: 'absolute', top: 4, width: 32, height: 5, borderRadius: 1, backgroundColor: yellow, transform: [{ rotate: '-8deg' }] },
  tassel: { position: 'absolute', top: 8, right: 4, width: 2, height: 10, borderRadius: 1, backgroundColor: yellow },
  head: { width: 14, height: 14, borderRadius: 7, backgroundColor: yellow, marginBottom: 2 },
  body: { width: 24, height: 14, borderTopLeftRadius: 12, borderTopRightRadius: 12, backgroundColor: yellow },
  board: { width: 30, height: 22, borderRadius: 3, borderWidth: 2, borderColor: yellow, padding: 5, gap: 4, marginBottom: 6 },
  chalk: { height: 2, width: 16, borderRadius: 1, backgroundColor: yellow },
  chalkShort: { width: 10 },
  apple: { position: 'absolute', right: 0, bottom: 2, width: 12, height: 12, borderRadius: 6, backgroundColor: '#FFFFFF' },
  leaf: { position: 'absolute', top: -4, right: 1, width: 6, height: 4, borderRadius: 2, backgroundColor: yellow, transform: [{ rotate: '28deg' }] },
  family: { flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'center', gap: 4 },
  adult: { alignItems: 'center' },
  adultHead: { width: 12, height: 12, borderRadius: 6, backgroundColor: yellow, marginBottom: 2 },
  adultBody: { width: 18, height: 16, borderTopLeftRadius: 9, borderTopRightRadius: 9, backgroundColor: yellow },
  child: { alignItems: 'center', marginBottom: 1 },
  childHead: { width: 9, height: 9, borderRadius: 5, backgroundColor: yellow, marginBottom: 2 },
  childBody: { width: 13, height: 12, borderTopLeftRadius: 7, borderTopRightRadius: 7, backgroundColor: yellow },
});
